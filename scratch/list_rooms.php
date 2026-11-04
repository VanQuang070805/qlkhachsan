<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
foreach (App\Models\RoomType::all() as $r) {
    echo "ID: {$r->id} | TypeName: {$r->type_name} | Price: {$r->price} | Image: {$r->image}\n";
}
