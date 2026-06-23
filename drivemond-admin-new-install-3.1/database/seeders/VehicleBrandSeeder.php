<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\VehicleManagement\Entities\VehicleBrand;
use Modules\VehicleManagement\Entities\VehicleModel;

class VehicleBrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // US Car Brands with Models
        $brandsData = [
            [
                'name' => 'Toyota',
                'is_active' => 1,
                'models' => ['Camry', 'Corolla', 'RAV4', 'Highlander', 'Tacoma', 'Tundra', '4Runner', 'Prius', 'Avalon', 'Sienna', 'Sequoia', 'Land Cruiser', 'C-HR', 'Venza', 'GR86', 'Supra', 'bZ4X', 'Crown']
            ],
            [
                'name' => 'Honda',
                'is_active' => 1,
                'models' => ['Civic', 'Accord', 'CR-V', 'HR-V', 'Pilot', 'Passport', 'Ridgeline', 'Odyssey', 'Accord Hybrid', 'Civic Hybrid', 'Prologue', 'e:Ny1']
            ],
            [
                'name' => 'Ford',
                'is_active' => 1,
                'models' => ['F-150', 'Mustang', 'Explorer', 'Escape', 'Edge', 'Bronco', 'Bronco Sport', 'Ranger', 'Maverick', 'Expedition', 'Transit', 'F-250 Super Duty', 'F-350 Super Duty', 'Mustang Mach-E', 'E-Transit']
            ],
            [
                'name' => 'Chevrolet',
                'is_active' => 1,
                'models' => ['Silverado', 'Equinox', 'Tahoe', 'Traverse', 'Malibu', 'Camaro', 'Corvette', 'Colorado', 'Trailblazer', 'Suburban', 'Blazer', 'Bolt EV', 'Bolt EUV', 'Spark', 'Trax', 'Express']
            ],
            [
                'name' => 'Ram',
                'is_active' => 1,
                'models' => ['1500', '2500', '3500', 'ProMaster', '1500 REV']
            ],
            [
                'name' => 'GMC',
                'is_active' => 1,
                'models' => ['Sierra', 'Yukon', 'Acadia', 'Terrain', 'Canyon', 'Hummer EV', 'Hummer EV Pickup', 'Denali']
            ],
            [
                'name' => 'Jeep',
                'is_active' => 1,
                'models' => ['Wrangler', 'Grand Cherokee', 'Cherokee', 'Compass', 'Renegade', 'Gladiator', 'Wagoneer', 'Grand Wagoneer', 'Avenger']
            ],
            [
                'name' => 'Nissan',
                'is_active' => 1,
                'models' => ['Altima', 'Sentra', 'Maxima', 'Versa', 'Rogue', 'Murano', 'Pathfinder', 'Armada', 'Frontier', 'Titan', 'Kicks', 'Leaf', 'Ariya', 'Z']
            ],
            [
                'name' => 'Hyundai',
                'is_active' => 1,
                'models' => ['Elantra', 'Sonata', 'Tucson', 'Santa Fe', 'Palisade', 'Kona', 'Venue', 'Ioniq 5', 'Ioniq 6', 'Santa Cruz', 'NEXO', 'Ioniq 5 N']
            ],
            [
                'name' => 'Kia',
                'is_active' => 1,
                'models' => ['Forte', 'K5', 'Optima', 'Sportage', 'Sorento', 'Telluride', 'Soul', 'Seltos', 'Stinger', 'Carnival', 'EV6', 'EV9', 'Niro', 'Niro EV']
            ],
            [
                'name' => 'BMW',
                'is_active' => 1,
                'models' => ['3 Series', '5 Series', '7 Series', 'X1', 'X3', 'X5', 'X7', 'X4', 'X6', 'Z4', 'M3', 'M4', 'M5', 'M8', 'i3', 'i4', 'i5', 'i7', 'iX', 'iX3', 'XM', '2 Series', '4 Series', '8 Series']
            ],
            [
                'name' => 'Mercedes-Benz',
                'is_active' => 1,
                'models' => ['A-Class', 'C-Class', 'E-Class', 'S-Class', 'GLA', 'GLB', 'GLC', 'GLE', 'GLS', 'G-Class', 'CLA', 'CLS', 'AMG GT', 'EQA', 'EQB', 'EQC', 'EQE', 'EQS', 'Maybach']
            ],
            [
                'name' => 'Audi',
                'is_active' => 1,
                'models' => ['A3', 'A4', 'A5', 'A6', 'A7', 'A8', 'Q3', 'Q5', 'Q7', 'Q8', 'e-tron', 'e-tron GT', 'RS e-tron GT', 'Q4 e-tron', 'Q6 e-tron', 'RS3', 'RS4', 'RS5', 'RS6', 'RS7', 'R8', 'TT']
            ],
            [
                'name' => 'Volkswagen',
                'is_active' => 1,
                'models' => ['Jetta', 'Passat', 'Arteon', 'Tiguan', 'Atlas', 'Taos', 'ID.4', 'ID.Buzz', 'Golf GTI', 'Golf R', 'Jetta GLI', 'Golf', 'T-Roc', 'Touareg']
            ],
            [
                'name' => 'Subaru',
                'is_active' => 1,
                'models' => ['Outback', 'Forester', 'Crosstrek', 'Impreza', 'Legacy', 'Ascent', 'WRX', 'BRZ', 'Solterra', 'Crosstrek Hybrid']
            ],
            [
                'name' => 'Mazda',
                'is_active' => 1,
                'models' => ['Mazda3', 'Mazda6', 'CX-3', 'CX-30', 'CX-5', 'CX-50', 'CX-9', 'CX-90', 'MX-5 Miata', 'MX-30', 'MX-5 RF']
            ],
            [
                'name' => 'Lexus',
                'is_active' => 1,
                'models' => ['ES', 'IS', 'LS', 'GS', 'RC', 'LC', 'NX', 'RX', 'GX', 'LX', 'UX', 'RZ', 'LFA', 'RC F', 'IS F', 'GS F', 'LC 500', 'NX 350h', 'RX 500h']
            ],
            [
                'name' => 'Tesla',
                'is_active' => 1,
                'models' => ['Model 3', 'Model Y', 'Model S', 'Model X', 'Cybertruck', 'Roadster', 'Semi', 'Model 3 Performance', 'Model Y Performance', 'Model S Plaid', 'Model X Plaid']
            ],
            [
                'name' => 'Dodge',
                'is_active' => 1,
                'models' => ['Challenger', 'Charger', 'Durango', 'Hornet', 'Charger Daytona', 'Challenger Daytona']
            ],
            [
                'name' => 'Chrysler',
                'is_active' => 1,
                'models' => ['300', 'Pacifica', 'Voyager', '300 SRT', 'Pacifica Hybrid']
            ],
            [
                'name' => 'Buick',
                'is_active' => 1,
                'models' => ['Enclave', 'Encore GX', 'Envision', 'Envista', 'Regal', 'LaCrosse']
            ],
            [
                'name' => 'Cadillac',
                'is_active' => 1,
                'models' => ['Escalade', 'XT4', 'XT5', 'XT6', 'CT4', 'CT5', 'Lyriq', 'Celestiq', 'CT4-V', 'CT5-V', 'Escalade-V', 'LYRIQ']
            ],
            [
                'name' => 'Lincoln',
                'is_active' => 1,
                'models' => ['Navigator', 'Aviator', 'Nautilus', 'Corsair', 'Continental', 'MKZ']
            ],
            [
                'name' => 'Acura',
                'is_active' => 1,
                'models' => ['MDX', 'RDX', 'TLX', 'ILX', 'Integra', 'ZDX', 'NSX', 'TLX Type S', 'MDX Type S']
            ],
            [
                'name' => 'Infiniti',
                'is_active' => 1,
                'models' => ['QX60', 'QX80', 'QX50', 'QX55', 'Q50', 'Q60', 'G35', 'G37', 'M35', 'FX35', 'FX45']
            ],
            [
                'name' => 'Genesis',
                'is_active' => 1,
                'models' => ['G80', 'G90', 'GV80', 'GV70', 'GV60', 'Electrified G80', 'Electrified GV70']
            ],
            [
                'name' => 'Volvo',
                'is_active' => 1,
                'models' => ['S60', 'S90', 'V60', 'V90', 'XC40', 'XC60', 'XC90', 'C40', 'EX90', 'EX30', 'XC40 Recharge']
            ],
            [
                'name' => 'Porsche',
                'is_active' => 1,
                'models' => ['911', '718 Cayman', '718 Boxster', 'Panamera', 'Cayenne', 'Macan', 'Taycan', '911 Turbo', '911 GT3', 'Cayenne E-Hybrid', 'Taycan Turbo']
            ],
            [
                'name' => 'Land Rover',
                'is_active' => 1,
                'models' => ['Range Rover', 'Range Rover Sport', 'Range Rover Velar', 'Range Rover Evoque', 'Defender', 'Discovery', 'Discovery Sport', 'Defender 90', 'Defender 110', 'Defender 130']
            ],
            [
                'name' => 'Mitsubishi',
                'is_active' => 1,
                'models' => ['Outlander', 'Eclipse Cross', 'Outlander PHEV', 'Mirage', 'Mirage G4', 'Triton', 'Pajero', 'Montero']
            ],
            [
                'name' => 'MINI',
                'is_active' => 1,
                'models' => ['Cooper', 'Cooper S', 'Clubman', 'Countryman', 'Convertible', 'Electric', 'John Cooper Works', 'Paceman', 'Hardtop 2 Door', 'Hardtop 4 Door']
            ],
            [
                'name' => 'Fiat',
                'is_active' => 1,
                'models' => ['500', '500X', '500L', '124 Spider', '500e', '500 Abarth']
            ],
            [
                'name' => 'Alfa Romeo',
                'is_active' => 1,
                'models' => ['Giulia', 'Stelvio', 'Tonale', 'Giulietta', 'Giulia Quadrifoglio', 'Stelvio Quadrifoglio', '4C']
            ],
            [
                'name' => 'Jaguar',
                'is_active' => 1,
                'models' => ['F-PACE', 'E-PACE', 'I-PACE', 'XE', 'XF', 'F-TYPE', 'XJ']
            ],
            [
                'name' => 'Maserati',
                'is_active' => 1,
                'models' => ['Ghibli', 'Quattroporte', 'Levante', 'MC20', 'GranTurismo', 'Grecale', 'GranCabrio']
            ],
            [
                'name' => 'Bentley',
                'is_active' => 1,
                'models' => ['Continental GT', 'Flying Spur', 'Bentayga', 'Mulliner', 'Continental GTC', 'Bentayga EWB', 'Continental GT Speed']
            ],
            [
                'name' => 'Rolls-Royce',
                'is_active' => 1,
                'models' => ['Phantom', 'Ghost', 'Wraith', 'Dawn', 'Cullinan', 'Spectre', 'Ghost Black Badge', 'Cullinan Black Badge', 'Phantom VIII']
            ],
            [
                'name' => 'Aston Martin',
                'is_active' => 1,
                'models' => ['DB11', 'DBS', 'Vantage', 'DBX', 'Valkyrie', 'V12 Speedster', 'DB12', 'DB12 Volante']
            ],
            [
                'name' => 'Ferrari',
                'is_active' => 1,
                'models' => ['296 GTB', '296 GTS', 'SF90 Stradale', 'SF90 Spider', 'F8 Tributo', 'F8 Spider', 'Roma', 'Portofino M', '812 Superfast', 'Purosangue', 'Daytona SP3', '812 GTS']
            ],
            [
                'name' => 'Lamborghini',
                'is_active' => 1,
                'models' => ['Huracan', 'Aventador', 'Urus', 'Revuelto', 'Huracan Tecnica', 'Huracan Sterrato', 'Aventador SVJ', 'Urus Performante', 'Urus S', 'Revuelto Spider']
            ],
            [
                'name' => 'Lucid',
                'is_active' => 1,
                'models' => ['Air Pure', 'Air Touring', 'Air Grand Touring', 'Air Sapphire', 'Gravity']
            ],
            [
                'name' => 'Rivian',
                'is_active' => 1,
                'models' => ['R1T', 'R1S', 'R1T Dual-Motor', 'R1S Dual-Motor', 'R1T Quad-Motor', 'R1S Quad-Motor', 'R1T Launch Edition']
            ],
            [
                'name' => 'Polestar',
                'is_active' => 1,
                'models' => ['Polestar 1', 'Polestar 2', 'Polestar 3', 'Polestar 4', 'Polestar 5', 'Polestar 6']
            ],
            [
                'name' => 'Hummer',
                'is_active' => 1,
                'models' => ['Hummer EV Pickup', 'Hummer EV SUV', 'Hummer EV Edition 1', 'Hummer EV 3X', 'Hummer EV 2X']
            ],
            [
                'name' => 'Scion',
                'is_active' => 0,
                'models' => ['FR-S', 'tC', 'iA', 'iM', 'iQ', 'xB', 'xD']
            ],
            [
                'name' => 'Suzuki',
                'is_active' => 0,
                'models' => ['Swift', 'Vitara', 'S-Cross', 'Jimny', 'Baleno', 'Ciaz', 'Dzire', 'Ertiga', 'XL7', 'Across', 'Swace']
            ]
        ];

        foreach ($brandsData as $brandData) {
            $brand = VehicleBrand::updateOrCreate(
                ['name' => $brandData['name']],
                ['is_active' => $brandData['is_active']]
            );

            foreach ($brandData['models'] as $modelName) {
                VehicleModel::updateOrCreate(
                    [
                        'brand_id' => $brand->id,
                        'name' => $modelName
                    ],
                    ['is_active' => $brandData['is_active']]
                );
            }
        }

        $this->command->info('Vehicle brands and models seeded successfully!');
    }
}
