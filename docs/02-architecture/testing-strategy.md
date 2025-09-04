# Testovací Strategie

## Aktuální Stav Testů
| Typ | Současné pokrytí / příklady |
|-----|-----------------------------|
| Feature (HTTP) | Ops dashboard přístup & permission testy, metrics endpoint (status + obsah) |
| Unit | `BackupStatusService` edge case (missing dir), ops help konfig testy |
| Factories | Model factories využívány v feature testech (User, OpsActivity) |
| Databáze | RefreshDatabase (sqlite fallback / migrace) |

Ukázkové existující testy:
- `tests/Feature/OpsMetricsContentTest.php` – validace metrik a gauge názvů.
- `tests/Feature/OpsDashboardAccessTest.php` – autorizace zobrazení.
- `tests/Unit/BackupStatusServiceTest.php` – logika fallbacku.

## Gaps / Chybějící Oblasti
| Oblast | Chybí | Důsledek |
|--------|-------|----------|
| Doménové modely (scopes) | Testy Opportunity lifecycle (won/lost), Contact normalizace | Riziko regresí v business logice |
| ActiveCampaign sync | Mock API + delta sync scénáře (rate limit, offset reset) | Nezajištěná robustnost integrace |
| Knowledge ingestion | Pipeline (upload → chunk → embedding → index) | Skrytá selhání při změnách embedding providerů |
| Permissions | Seed & enforcement test (ops.view vs. ostatní) | Tiché oprávnění chyby |
| Queue jobs | Idempotence + retry logic testy | Potenciální duplikace / race conditions |
| Schedule | Kontrola, že kritické joby jsou naplánovány (snapshot test) | Nechtěné vypuštění úlohy |
| Backup verify | Simulace fake dump + verify log parsing | Verify může degradovat bez povšimnutí |
| API Rate limiting | Throttle testy (chat, search) | Možné DoS chování |
| Metrics coverage | Test přítomnosti všech očekávaných metrik (snapshot) | Tichá ztráta metrik |

## Doporučený Roadmap Testů
1. Domain Behavior Suite
   - Opportunitiy: transitions → won/lost sets closed_at; probability guard.
   - Contact: email/phone normalization (valid/invalid cases).
2. Integrace: ActiveCampaign Mock Server
   - Simulace 200, 403 (rate limit), schéma změna (neočekávané pole) – očekávané handling.
3. Knowledge Pipeline
   - Fake text upload → generovaný počet chunků → index existence.
4. Permissions & ACL
   - Matrix test: role vs. resource gates (403 vs 200) – snapshot.
5. Backup / Verify E2E (tag @slow)
   - Generovat malý test dump + verify restore job → assert OpsActivity success.
6. Schedule Assertions
   - Test, že Kernel obsahuje očekávané patterny (regex extrakce).
7. Metrics Snapshot
   - Generace kontrolního seznamu metrik do fixture, test porovná set (ignoruje hodnoty).
8. Job Idempotence
   - Spuštění jobu dvakrát (např. ActiveCampaignSyncJob) – bez duplikátů / ztrát.

## Nástroje a Praktiky
| Téma | Doporučení |
|------|------------|
| Test dat | Minimální factories; žádné masivní seedery v unit testech |
| Izolace integrací | Mock HTTP (HTTP::fake) pro ActiveCampaign/Qdrant v unit level |
| Rychlé zpětné vazby | Oddělit `@slow` skupinu (verify, pipeline) do separátního GitHub jobu |
| Coverage | Přidat `Xdebug` nebo `pcov` jen v coverage workflow (volitelné) |
| Mutace | Striker / Infection (volitelně) pro kritické business funkce |

## Metriky Kvality (cílové)
| Metrika | Cíl |
|---------|-----|
| Feature business kritické flows pokryty | ≥ 80% kritických cest |
| Integrace (mock scénáře) | ≥ 3 klíčové varianty na integraci |
| Idempotence jobů testována | Top 5 kritických jobů |
| Permission matrix test | 100% definovaných gate rout |

## Příklad Template pro Nový Feature Test
```php
public function test_contact_email_is_normalized(): void
{
    $user = User::factory()->create();
    $this->actingAs($user);
    $resp = $this->post('/crm/contacts', [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => '  JOHN.DOE+tag@Example.COM  ',
    ]);
    $resp->assertRedirect();
    $contact = Contact::first();
    $this->assertEquals('john.doe@example.com', $contact->normalized_email);
}
```

---
Aktualizovat při přidání nových integračních služeb nebo modulů.
