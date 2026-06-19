<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        if (! Category::where('slug', 'huis-en-tuin')->exists()) {
            Category::where('slug', 'tuin')->update(['slug' => 'huis-en-tuin']);
        }

        if (! Category::where('slug', 'wassen-kledingonderhoud')->exists()) {
            Category::where('slug', 'wassen-kleding')->update(['slug' => 'wassen-kledingonderhoud']);
        }

        if (! Category::where('slug', 'ai-trucjes')->exists()) {
            Category::where('slug', 'omas-oudste-trucjes')->update(['slug' => 'ai-trucjes']);
        }

        $categories = [
            ['Vlekken', 'vlekken', '🧼', '#3b82f6', 'Vlekken uit kleding, meubels, tapijt en tafelkleden krijgen.'],
            ['Gezondheid', 'gezondheid', '🩺', '#ef4444', 'Klachten, kwaaltjes en gezonde gewoontes.'],
            ['Diversen', 'diversen', '🔧', '#94a3b8', 'Alles wat nergens anders past.'],
            ['Keuken', 'keuken', '🍽️', '#f97316', 'Apparaten, gerei en handigheidjes.'],
            ['Huis en Tuin', 'huis-en-tuin', '🌱', '#22c55e', 'Planten, gras, onkruid, kleine klussen en buitenleven.'],
            ['Schoonmaken', 'schoonmaken', '🧽', '#06b6d4', 'Het huis fris en schoon houden.'],
            ['Werk en inkomen', 'werk-en-inkomen', '💼', '#0d9488', 'Werk, geld en administratie.'],
            ['Huisdieren', 'huisdieren', '🐾', '#a855f7', 'Verzorging en gedrag van je dieren.'],
            ['Wassen & kledingonderhoud', 'wassen-kledingonderhoud', '👕', '#0ea5e9', 'Wasvoorschriften, kleding redden en textiel mooi houden.'],
            ['Computers & elektronica', 'computers-elektronica', '💻', '#6366f1', 'Apparaten, software en instellingen.'],
            ['Eten en drinken', 'eten-en-drinken', '🍳', '#f59e0b', 'Recepten, bewaren en keukenkennis.'],
            ['Hobby', 'hobby', '🎨', '#d946ef', 'Knutselen, maken en creatief bezig zijn.'],
            ['AI-trucjes', 'ai-trucjes', '✨', '#b45309', 'Slimme huis-, tuin- en keukenoplossingen in een AI-jasje.'],
            ['Uiterlijk & Verzorging', 'uiterlijk-verzorging', '💇', '#ec4899', 'Huid, haar en persoonlijke verzorging.'],
            ['Vervoer & Auto', 'vervoer-auto', '🚗', '#64748b', 'Onderweg, onderhoud en reizen.'],
            ['Duurzaamheid', 'duurzaamheid', '♻️', '#84cc16', 'Besparen, hergebruiken en groener leven.'],
        ];

        foreach ($categories as $i => [$name, $slug, $icon, $color, $description]) {
            Category::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'icon' => $icon,
                    'color' => $color,
                    'description' => $description,
                    'sort_order' => $i,
                ]
            );
        }
    }
}
