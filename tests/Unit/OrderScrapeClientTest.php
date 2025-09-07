<?php

namespace Tests\Unit;

use App\Services\Orders\OrderScrapeClient;
use App\Services\Orders\DTO\ListingOrderRowDTO;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;
use Traversable;
use PHPUnit\Framework\TestCase as BaseTestCase;

class OrderScrapeClientTest extends BaseTestCase
{
    private function mockSequence(array $responses): HttpClientInterface
    {
        return new class($responses) implements HttpClientInterface {
            private array $responses; private int $i = 0;
            public function __construct($responses) { $this->responses = $responses; }
            public function request(string $method, string $url, array $options = []): ResponseInterface {
                $current = $this->responses[$this->i] ?? end($this->responses);
                $this->i++;
                return new class($current) implements ResponseInterface {
                    public function __construct(private array $cfg) {}
                    public function getStatusCode(): int { return $this->cfg['status'] ?? 200; }
                    public function getHeaders(bool $throw = true): array { return ['set-cookie'=>[]]; }
                    public function getContent(bool $throw = true): string { return $this->cfg['body'] ?? ''; }
                    public function toArray(bool $throw = true): array { return []; }
                    public function cancel(): void {}
                    public function getInfo(?string $type = null): mixed { return null; }
                };
            }
            public function stream(ResponseInterface|Traversable|array $responses, ?float $timeout = null): ResponseStreamInterface {
                return new class implements ResponseStreamInterface {
                    public function current(): mixed { return null; }
                    public function next(): void {}
                    public function key(): mixed { return null; }
                    public function valid(): bool { return false; }
                    public function rewind(): void {}
                    public function getIterator(): Traversable { yield from []; }
                };
            }
            public function withOptions(array $options): static { return $this; }
        };
    }

    public function testListingParsing()
    {
        $htmlLogin = '<html><form><input name="_token" value="abc"></form></html>';
        $listing = file_get_contents(__DIR__.'/../Fixtures/orders/listing_page_1.html');
        $client = new class($this->mockSequence([
            ['body'=>$htmlLogin],
            ['body'=>'<html></html>'],
            ['body'=>$listing],
        ])) extends OrderScrapeClient {
            public function __construct($mock) { $this->baseUrl='http://example.test'; $this->http=$mock; }
            public function login(): void {
                // consume two mock responses to simulate GET+POST login steps
                $this->http->request('GET', $this->baseUrl.'/admin/');
                $this->http->request('POST', $this->baseUrl.'/admin/');
            }
        };
        $client->login();
        $rows = $client->fetchListingPage(1);
        $this->assertCount(2, $rows);
        $this->assertEquals('100001', $rows[0]->orderNumber);
        $this->assertEquals(123400, $rows[0]->totalVatCents);
    }

    public function testDetailParsing()
    {
        $htmlLogin = '<html><form><input name="_token" value="abc"></form></html>';
        $detail = file_get_contents(__DIR__.'/../Fixtures/orders/detail_100001.html');
        $client = new class($this->mockSequence([
            ['body'=>$htmlLogin],
            ['body'=>'<html></html>'],
            ['body'=>$detail],
        ])) extends OrderScrapeClient {
            public function __construct($mock) { $this->baseUrl='http://example.test'; $this->http=$mock; }
            public function login(): void {
                $this->http->request('GET', $this->baseUrl.'/admin/');
                $this->http->request('POST', $this->baseUrl.'/admin/');
            }
        };
        $client->login();
        $dto = $client->fetchDetail('100001');
        $this->assertEquals('100001', $dto->row->orderNumber);
        $this->assertNotEmpty($dto->rawHash);
        $this->assertGreaterThanOrEqual(3, count($dto->items));
    }
}
