<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Sample data for a small product team. Every account's password is "password".
 * Model events are muted so seeding does not queue assignment emails or fill the SSE feed.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $password = Hash::make('password');

        $users = collect([
            ['Admin Lajur', 'admin@lajur.test', User::ROLE_ADMIN],
            ['Rina Pratama', 'rina@lajur.test', User::ROLE_MEMBER],
            ['Bagus Santoso', 'bagus@lajur.test', User::ROLE_MEMBER],
            ['Dewi Kartika', 'dewi@lajur.test', User::ROLE_MEMBER],
            ['Fajar Nugroho', 'fajar@lajur.test', User::ROLE_MEMBER],
            ['Sari Wulandari', 'sari@lajur.test', User::ROLE_MEMBER],
        ])->mapWithKeys(fn ($u) => [explode('@', $u[1])[0] => User::create([
            'name' => $u[0],
            'email' => $u[1],
            'password' => $password,
            'role' => $u[2],
        ])]);

        // [title, description, status, priority, assignee, creator, due in days]
        $tasks = [
            ['Set up staging database', 'Provision MySQL 8 on the staging box and import last week\'s anonymised dump.', 'done', 'high', 'bagus', 'admin', -6],
            ['Write onboarding checklist', 'One page for new hires: accounts, repo access, local setup.', 'done', 'low', 'sari', 'admin', -3],
            ['Fix password reset email', 'Reset link points to localhost in production. Check APP_URL on the mail worker.', 'done', 'high', 'rina', 'dewi', -2],
            ['Invoice PDF shows wrong tax rate', 'Customers in Bali see 10% instead of 11%. Rate is hard-coded in InvoiceRenderer.', 'in_progress', 'high', 'rina', 'admin', 1],
            ['Migrate cron jobs to the scheduler', 'Move the three crontab entries on web-01 into routes/console.php.', 'in_progress', 'medium', 'bagus', 'bagus', 4],
            ['Compress product images on upload', 'Originals are 4 to 8 MB. Resize to 1600px max and keep the original in cold storage.', 'in_progress', 'medium', 'fajar', 'dewi', 6],
            ['Draft Q4 roadmap', 'Collect requests from support and sales, rank by effort.', 'in_progress', 'medium', 'admin', 'admin', 9],
            ['Add CSV import for customers', 'Sales wants to bulk import leads from trade fairs. Validate email and phone.', 'todo', 'medium', 'dewi', 'sari', 12],
            ['Review warehouse API contract', 'Partner sent v2 of the spec. Check breaking changes against our client.', 'todo', 'high', 'fajar', 'admin', 3],
            ['Rotate S3 access keys', 'Keys are 14 months old. Rotate and update the secret store.', 'todo', 'high', 'bagus', 'admin', 2],
            ['Mobile layout for order history', 'Table overflows below 400px. Switch to stacked rows.', 'todo', 'medium', 'sari', 'dewi', 10],
            ['Clean up unused feature flags', 'At least 9 flags have been 100% on for months.', 'todo', 'low', null, 'rina', null],
            ['Load test checkout', 'Target 200 concurrent sessions. Use k6 against staging.', 'todo', 'medium', 'fajar', 'bagus', 15],
            ['Update privacy policy page', 'Legal sent new wording for the data retention section.', 'todo', 'low', 'sari', 'admin', 20],
            ['Investigate slow search', 'Product search takes 3s with more than 2 filters. Check missing indexes.', 'todo', 'high', 'rina', 'fajar', 5],
            ['Archive old support tickets', 'Tickets closed before 2025 can move to the archive table.', 'todo', 'low', null, 'dewi', null],
            ['Write release notes for 2.4', null, 'todo', 'low', 'dewi', 'admin', 7],
            ['Replace deprecated payment SDK', 'Vendor ends support for v3 at the end of the quarter.', 'todo', 'high', 'bagus', 'rina', 18],
        ];

        $created = collect($tasks)->map(fn ($t, $i) => Task::forceCreate([
            'title' => $t[0],
            'description' => $t[1],
            'status' => $t[2],
            'priority' => $t[3],
            'assigned_user_id' => $t[4] ? $users[$t[4]]->id : null,
            'created_by' => $users[$t[5]]->id,
            'due_date' => $t[6] === null ? null : now()->addDays($t[6])->toDateString(),
            'created_at' => now()->subDays(20 - $i),
            'updated_at' => now()->subDays(max(0, 15 - $i)),
        ]));

        // [task index, author, comment]
        $comments = [
            [3, 'rina', 'Found it. The rate comes from a config value that was never updated after April.'],
            [3, 'admin', 'Please add a test with a Bali address so this does not come back.'],
            [3, 'rina', 'Test added. Waiting on review.'],
            [4, 'bagus', 'Two of the three jobs are moved. The report job needs a lock first.'],
            [5, 'fajar', 'Going with GD for now. Imagick is not installed on web-02.'],
            [5, 'dewi', 'Fine by me, as long as EXIF orientation is respected.'],
            [8, 'fajar', 'Their v2 renames sku to item_code. That breaks our sync job.'],
            [8, 'admin', 'Let us ask them for a deprecation window before we change anything.'],
            [9, 'bagus', 'Scheduled for Thursday night, low traffic window.'],
            [14, 'rina', 'EXPLAIN shows a full scan on product_tags. Adding a composite index.'],
            [14, 'fajar', 'Search is down to 400ms on staging with the index.'],
            [0, 'bagus', 'Done. Credentials are in the team vault.'],
            [2, 'dewi', 'Confirmed fixed in production, thanks.'],
            [10, 'sari', 'I will pair with Dewi on this one, she knows the table component.'],
        ];

        foreach ($comments as $i => [$taskIndex, $author, $text]) {
            TaskComment::forceCreate([
                'task_id' => $created[$taskIndex]->id,
                'user_id' => $users[$author]->id,
                'comment' => $text,
                'created_at' => $created[$taskIndex]->created_at->copy()->addHours(3 + $i),
            ]);
        }
    }
}
