<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Models\User;

class ImportEslUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * --update-md  Update esl-kontakty.md with generated passwords
     */
    protected $signature = 'esl:import-users {--update-md}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import users from esl-kontakty.md, generate passwords, export CSV and update markdown';

    public function handle()
    {
        $mdPath = base_path('docs/17-external/esl-kontakty.md');
        if (!file_exists($mdPath)) {
            $this->error("Soubor esl-kontakty.md nenalezen: {$mdPath}");
            return 1;
        }

        $lines = file($mdPath, FILE_IGNORE_NEW_LINES);
        $users = [];
        // Parse contacts: name in **...**, email: line
        for ($i = 0; $i < count($lines); $i++) {
            if (preg_match('/\*\*(.+?)\*\*/', $lines[$i], $m)) {
                $name = trim($m[1]);
                // look ahead for Email:
                $email = null;
                for ($j = $i + 1; $j < min($i + 5, count($lines)); $j++) {
                    if (preg_match('/Email:\s*(\S+)/i', $lines[$j], $me)) {
                        $email = trim($me[1]);
                        break;
                    }
                }
                if ($email) {
                    $pwd = Str::random(12);
                    $users[] = ['name' => $name, 'email' => $email, 'password' => $pwd, 'lineIndex' => $j];
                }
            }
        }

        if (empty($users)) {
            $this->info('Žádní uživatelé k importu.');
            return 0;
        }

        // Write CSV
        $csvPath = storage_path('app/esl-users-import.csv');
        $fh = fopen($csvPath, 'w');
        fputcsv($fh, ['name','email','password']);
        foreach ($users as $u) {
            fputcsv($fh, [$u['name'], $u['email'], $u['password']]);
        }
        fclose($fh);
        $this->info("CSV soubor vytvořen: {$csvPath}");

        // Import do databáze (bez odesílání e-mailů)
        foreach ($users as $u) {
            try {
                $user = User::firstOrNew(['email' => $u['email']]);
                $user->name = $u['name'];
                $user->password = $u['password'];
                $user->save();
                $this->info("Uživatel {$u['email']} vytvořen.");
            } catch (\Exception $e) {
                $this->error("Chyba při importu {$u['email']}: {$e->getMessage()}");
            }
        }

        $this->info('Hotovo.');
        return 0;
    }
}
