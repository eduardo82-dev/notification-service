<?php
declare(strict_types=1);
namespace Database\Seeders;
use App\Infrastructure\Persistence\Models\NotificationStatusModel;
use App\Infrastructure\Persistence\Models\NotificationTypeModel;
use Illuminate\Database\Seeder;
final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['pending','processing','sent','failed','partial'] as $s) {
            NotificationStatusModel::firstOrCreate(['name' => $s], ['description' => '']);
        }
        foreach ([['order_paid','Order payment'],['password_reset','Password reset'],['email_verification','Email verify']] as $t) {
            NotificationTypeModel::firstOrCreate(['name' => $t[0]], ['description' => $t[1]]);
        }
    }
}
