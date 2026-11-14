<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
class LaptopToken extends Command {
 protected $signature='laptop:token';protected $description='Maak een sleutel voor de laptopworker (dit wijzigt je .env niet)';
 public function handle():int {$key=bin2hex(random_bytes(32));$this->line('Laptop worker.json / token: '.$key);$this->line('Server .env: LAPTOP_IMAGE_TOKEN_HASH='.hash('sha256',$key));$this->warn('Bewaar de sleutel alleen op je eigen laptop. Deel hem niet via chat of git.');return self::SUCCESS;}
}
