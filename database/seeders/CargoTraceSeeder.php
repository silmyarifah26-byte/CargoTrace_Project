<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Carrier;
use App\Models\Location;
use App\Models\Notification;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\ShipmentItem;
use App\Models\TrackingEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CargoTraceSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Users (Admin, Logistics Operator, Customers)
        $admin = User::updateOrCreate(
            ['email' => 'admin@cargotrace.test'],
            [
                'name' => 'Admin CargoTrace',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        $operator = User::updateOrCreate(
            ['email' => 'operator@cargotrace.test'],
            [
                'name' => 'Raka Pratama (Operator)',
                'password' => Hash::make('password123'),
                'role' => 'operator',
            ]
        );

        $customer1 = User::updateOrCreate(
            ['email' => 'customer1@cargotrace.test'],
            [
                'name' => 'PT Nusantara Ekspor',
                'password' => Hash::make('password123'),
                'role' => 'customer',
            ]
        );

        $customer2 = User::updateOrCreate(
            ['email' => 'customer2@cargotrace.test'],
            [
                'name' => 'CV Samudra Niaga',
                'password' => Hash::make('password123'),
                'role' => 'customer',
            ]
        );

        // 2. Carriers (Maritime Vessel Operators)
        $maersk = Carrier::updateOrCreate(
            ['code' => 'MAEU'],
            [
                'name' => 'Maersk Line Global',
                'contact_email' => 'ops@maersk-demo.test',
                'contact_phone' => '+65-6323-8323',
                'is_active' => true,
            ]
        );

        $msc = Carrier::updateOrCreate(
            ['code' => 'MSCU'],
            [
                'name' => 'Mediterranean Shipping Co.',
                'contact_email' => 'support@msc-demo.test',
                'contact_phone' => '+41-22-703-8888',
                'is_active' => true,
            ]
        );

        $pil = Carrier::updateOrCreate(
            ['code' => 'PILU'],
            [
                'name' => 'Pacific International Lines',
                'contact_email' => 'tracking@pil-demo.test',
                'contact_phone' => '+65-6221-8133',
                'is_active' => true,
            ]
        );

        // 3. Seaports — 100 Ports Across Indonesia & Global Countries
        $ports = [
            // --- Indonesia (1–22) ---
            'jakarta' => [
                'code' => 'IDJKT',
                'name' => 'Port of Tanjung Priok',
                'city' => 'Jakarta',
                'country' => 'Indonesia',
                'latitude' => -6.1025000,
                'longitude' => 106.8794000,
            ],
            'surabaya' => [
                'code' => 'IDSUB',
                'name' => 'Port of Tanjung Perak',
                'city' => 'Surabaya',
                'country' => 'Indonesia',
                'latitude' => -7.2028000,
                'longitude' => 112.7364000,
            ],
            'belawan' => [
                'code' => 'IDBWN',
                'name' => 'Port of Belawan',
                'city' => 'Medan',
                'country' => 'Indonesia',
                'latitude' => 3.7847000,
                'longitude' => 98.6856000,
            ],
            'makassar' => [
                'code' => 'IDUPG',
                'name' => 'Port of Soekarno-Hatta Makassar',
                'city' => 'Makassar',
                'country' => 'Indonesia',
                'latitude' => -5.1256000,
                'longitude' => 119.4067000,
            ],
            'kualaTanjung' => [
                'code' => 'IDKTG',
                'name' => 'Port of Kuala Tanjung',
                'city' => 'Batubara',
                'country' => 'Indonesia',
                'latitude' => 3.3689000,
                'longitude' => 99.4561000,
            ],
            'malahayati' => [
                'code' => 'IDMHY',
                'name' => 'Port of Malahayati Krueng Raya',
                'city' => 'Aceh Besar',
                'country' => 'Indonesia',
                'latitude' => 5.5867000,
                'longitude' => 95.5242000,
            ],
            'semarang' => [
                'code' => 'IDSRG',
                'name' => 'Port of Tanjung Emas',
                'city' => 'Semarang',
                'country' => 'Indonesia',
                'latitude' => -6.9489000,
                'longitude' => 110.4225000,
            ],
            'padang' => [
                'code' => 'IDPDG',
                'name' => 'Port of Teluk Bayur',
                'city' => 'Padang',
                'country' => 'Indonesia',
                'latitude' => -1.0014000,
                'longitude' => 100.3728000,
            ],
            'balikpapan' => [
                'code' => 'IDBPN',
                'name' => 'Port of Semayang & Kariangau',
                'city' => 'Balikpapan',
                'country' => 'Indonesia',
                'latitude' => -1.2683000,
                'longitude' => 116.8056000,
            ],
            'bitung' => [
                'code' => 'IDBIT',
                'name' => 'International Hub Port of Bitung',
                'city' => 'Bitung',
                'country' => 'Indonesia',
                'latitude' => 1.4408000,
                'longitude' => 125.1936000,
            ],
            'lhokseumawe' => [
                'code' => 'IDLSM',
                'name' => 'Port of Krueng Geukueh',
                'city' => 'Lhokseumawe',
                'country' => 'Indonesia',
                'latitude' => 5.2397000,
                'longitude' => 97.0225000,
            ],
            'sabang' => [
                'code' => 'IDSAB',
                'name' => 'Port of Sabang (BPKS)',
                'city' => 'Sabang',
                'country' => 'Indonesia',
                'latitude' => 5.8942000,
                'longitude' => 95.3211000,
            ],
            'dumai' => [
                'code' => 'IDDUM',
                'name' => 'Port of Dumai',
                'city' => 'Dumai',
                'country' => 'Indonesia',
                'latitude' => 1.6814000,
                'longitude' => 101.4475000,
            ],
            'palembang' => [
                'code' => 'IDPLM',
                'name' => 'Port of Boom Baru Palembang',
                'city' => 'Palembang',
                'country' => 'Indonesia',
                'latitude' => -2.9761000,
                'longitude' => 104.7754000,
            ],
            'merak' => [
                'code' => 'IDMRK',
                'name' => 'Port of Merak',
                'city' => 'Cilegon',
                'country' => 'Indonesia',
                'latitude' => -5.9250000,
                'longitude' => 105.9983000,
            ],
            'cirebon' => [
                'code' => 'IDCRB',
                'name' => 'Port of Cirebon',
                'city' => 'Cirebon',
                'country' => 'Indonesia',
                'latitude' => -6.7064000,
                'longitude' => 108.5672000,
            ],
            'gresik' => [
                'code' => 'IDGRS',
                'name' => 'Port of Gresik',
                'city' => 'Gresik',
                'country' => 'Indonesia',
                'latitude' => -7.1567000,
                'longitude' => 112.6556000,
            ],
            'pontianak' => [
                'code' => 'IDPNK',
                'name' => 'Port of Pontianak (Dwikora)',
                'city' => 'Pontianak',
                'country' => 'Indonesia',
                'latitude' => -0.0263000,
                'longitude' => 109.3425000,
            ],
            'banjarmasin' => [
                'code' => 'IDBDJ',
                'name' => 'Port of Trisakti Banjarmasin',
                'city' => 'Banjarmasin',
                'country' => 'Indonesia',
                'latitude' => -3.3278000,
                'longitude' => 114.5586000,
            ],
            'samarinda' => [
                'code' => 'IDSRI',
                'name' => 'Port of Samarinda',
                'city' => 'Samarinda',
                'country' => 'Indonesia',
                'latitude' => -0.5022000,
                'longitude' => 117.1536000,
            ],
            'sorong' => [
                'code' => 'IDSOQ',
                'name' => 'Port of Sorong',
                'city' => 'Sorong',
                'country' => 'Indonesia',
                'latitude' => -0.8761000,
                'longitude' => 131.2558000,
            ],
            'jayapura' => [
                'code' => 'IDDJJ',
                'name' => 'Port of Jayapura',
                'city' => 'Jayapura',
                'country' => 'Indonesia',
                'latitude' => -2.5337000,
                'longitude' => 140.7181000,
            ],

            // --- Asia Tenggara (23–32) ---
            'singapore' => [
                'code' => 'SGSIN',
                'name' => 'Port of Singapore (PSA)',
                'city' => 'Singapore',
                'country' => 'Singapore',
                'latitude' => 1.2644000,
                'longitude' => 103.8223000,
            ],
            'tanjungPelepas' => [
                'code' => 'MYTPP',
                'name' => 'Port of Tanjung Pelepas',
                'city' => 'Johor',
                'country' => 'Malaysia',
                'latitude' => 1.3636000,
                'longitude' => 103.5506000,
            ],
            'portKlang' => [
                'code' => 'MYPKG',
                'name' => 'Port Klang',
                'city' => 'Selangor',
                'country' => 'Malaysia',
                'latitude' => 2.9994000,
                'longitude' => 101.3917000,
            ],
            'penang' => [
                'code' => 'MYPEN',
                'name' => 'Port of Penang (North Butterworth)',
                'city' => 'George Town',
                'country' => 'Malaysia',
                'latitude' => 5.4141000,
                'longitude' => 100.3488000,
            ],
            'laemChabang' => [
                'code' => 'THLCH',
                'name' => 'Port of Laem Chabang',
                'city' => 'Chonburi',
                'country' => 'Thailand',
                'latitude' => 13.0825000,
                'longitude' => 100.8833000,
            ],
            'hoChiMinh' => [
                'code' => 'VNSGN',
                'name' => 'Port of Ho Chi Minh (Cat Lai)',
                'city' => 'Ho Chi Minh City',
                'country' => 'Vietnam',
                'latitude' => 10.7683000,
                'longitude' => 106.7917000,
            ],
            'haiPhong' => [
                'code' => 'VNHPH',
                'name' => 'Port of Hai Phong',
                'city' => 'Hai Phong',
                'country' => 'Vietnam',
                'latitude' => 20.8449000,
                'longitude' => 106.6881000,
            ],
            'manila' => [
                'code' => 'PHMNL',
                'name' => 'Port of Manila',
                'city' => 'Manila',
                'country' => 'Philippines',
                'latitude' => 14.5897000,
                'longitude' => 120.9639000,
            ],
            'cebu' => [
                'code' => 'PHCEB',
                'name' => 'Port of Cebu',
                'city' => 'Cebu City',
                'country' => 'Philippines',
                'latitude' => 10.3157000,
                'longitude' => 123.8854000,
            ],
            'sihanoukville' => [
                'code' => 'KHKOS',
                'name' => 'Sihanoukville Autonomous Port',
                'city' => 'Sihanoukville',
                'country' => 'Cambodia',
                'latitude' => 10.6333000,
                'longitude' => 103.5000000,
            ],

            // --- Asia Timur (33–47) ---
            'shanghai' => [
                'code' => 'CNSHA',
                'name' => 'Port of Shanghai',
                'city' => 'Shanghai',
                'country' => 'China',
                'latitude' => 31.2304000,
                'longitude' => 121.4737000,
            ],
            'ningbo' => [
                'code' => 'CNNGB',
                'name' => 'Port of Ningbo-Zhoushan',
                'city' => 'Ningbo',
                'country' => 'China',
                'latitude' => 29.8683000,
                'longitude' => 121.5440000,
            ],
            'shenzhen' => [
                'code' => 'CNSZX',
                'name' => 'Port of Shenzhen (Yantian/Shekou)',
                'city' => 'Shenzhen',
                'country' => 'China',
                'latitude' => 22.5431000,
                'longitude' => 114.0579000,
            ],
            'guangzhou' => [
                'code' => 'CNCAN',
                'name' => 'Port of Guangzhou (Nansha)',
                'city' => 'Guangzhou',
                'country' => 'China',
                'latitude' => 22.6333000,
                'longitude' => 113.5833000,
            ],
            'qingdao' => [
                'code' => 'CNTAO',
                'name' => 'Port of Qingdao',
                'city' => 'Qingdao',
                'country' => 'China',
                'latitude' => 36.0671000,
                'longitude' => 120.3826000,
            ],
            'tianjin' => [
                'code' => 'CNTSN',
                'name' => 'Port of Tianjin',
                'city' => 'Tianjin',
                'country' => 'China',
                'latitude' => 39.0000000,
                'longitude' => 117.7833000,
            ],
            'dalian' => [
                'code' => 'CNDLC',
                'name' => 'Port of Dalian',
                'city' => 'Dalian',
                'country' => 'China',
                'latitude' => 38.9140000,
                'longitude' => 121.6147000,
            ],
            'xiamen' => [
                'code' => 'CNXMN',
                'name' => 'Port of Xiamen',
                'city' => 'Xiamen',
                'country' => 'China',
                'latitude' => 24.4798000,
                'longitude' => 118.0894000,
            ],
            'hongKong' => [
                'code' => 'HKHKG',
                'name' => 'Port of Hong Kong',
                'city' => 'Hong Kong',
                'country' => 'Hong Kong',
                'latitude' => 22.2855000,
                'longitude' => 114.1577000,
            ],
            'busan' => [
                'code' => 'KRPUS',
                'name' => 'Port of Busan',
                'city' => 'Busan',
                'country' => 'South Korea',
                'latitude' => 35.1028000,
                'longitude' => 129.0403000,
            ],
            'incheon' => [
                'code' => 'KRICN',
                'name' => 'Port of Incheon',
                'city' => 'Incheon',
                'country' => 'South Korea',
                'latitude' => 37.4563000,
                'longitude' => 126.7052000,
            ],
            'tokyo' => [
                'code' => 'JPTYO',
                'name' => 'Port of Tokyo',
                'city' => 'Tokyo',
                'country' => 'Japan',
                'latitude' => 35.6197000,
                'longitude' => 139.7764000,
            ],
            'yokohama' => [
                'code' => 'JPYOK',
                'name' => 'Port of Yokohama',
                'city' => 'Yokohama',
                'country' => 'Japan',
                'latitude' => 35.4437000,
                'longitude' => 139.6380000,
            ],
            'kobe' => [
                'code' => 'JPUKB',
                'name' => 'Port of Kobe',
                'city' => 'Kobe',
                'country' => 'Japan',
                'latitude' => 34.6901000,
                'longitude' => 135.1955000,
            ],
            'kaohsiung' => [
                'code' => 'TWKHH',
                'name' => 'Port of Kaohsiung',
                'city' => 'Kaohsiung',
                'country' => 'Taiwan',
                'latitude' => 22.6163000,
                'longitude' => 120.2833000,
            ],

            // --- Asia Selatan & Timur Tengah (48–54) ---
            'mumbai' => [
                'code' => 'INBOM',
                'name' => 'Jawaharlal Nehru Port (Nhava Sheva)',
                'city' => 'Mumbai',
                'country' => 'India',
                'latitude' => 18.9499000,
                'longitude' => 72.9503000,
            ],
            'chennai' => [
                'code' => 'INMAA',
                'name' => 'Chennai Port',
                'city' => 'Chennai',
                'country' => 'India',
                'latitude' => 13.0827000,
                'longitude' => 80.2950000,
            ],
            'colombo' => [
                'code' => 'LKCMB',
                'name' => 'Port of Colombo',
                'city' => 'Colombo',
                'country' => 'Sri Lanka',
                'latitude' => 6.9497000,
                'longitude' => 79.8428000,
            ],
            'jebelAli' => [
                'code' => 'AEJEA',
                'name' => 'Port of Jebel Ali',
                'city' => 'Dubai',
                'country' => 'United Arab Emirates',
                'latitude' => 25.0113000,
                'longitude' => 55.0612000,
            ],
            'khalifaPort' => [
                'code' => 'AEKHL',
                'name' => 'Khalifa Port Abu Dhabi',
                'city' => 'Abu Dhabi',
                'country' => 'United Arab Emirates',
                'latitude' => 24.8089000,
                'longitude' => 54.6456000,
            ],
            'jeddah' => [
                'code' => 'SAJED',
                'name' => 'Jeddah Islamic Port',
                'city' => 'Jeddah',
                'country' => 'Saudi Arabia',
                'latitude' => 21.4858000,
                'longitude' => 39.1925000,
            ],
            'salalah' => [
                'code' => 'OMSLL',
                'name' => 'Port of Salalah',
                'city' => 'Salalah',
                'country' => 'Oman',
                'latitude' => 16.9417000,
                'longitude' => 53.9986000,
            ],

            // --- Eropa (55–68) ---
            'rotterdam' => [
                'code' => 'NLRTM',
                'name' => 'Port of Rotterdam',
                'city' => 'Rotterdam',
                'country' => 'Netherlands',
                'latitude' => 51.9496000,
                'longitude' => 4.1453000,
            ],
            'antwerp' => [
                'code' => 'BEANR',
                'name' => 'Port of Antwerp-Bruges',
                'city' => 'Antwerp',
                'country' => 'Belgium',
                'latitude' => 51.2494000,
                'longitude' => 4.4003000,
            ],
            'hamburg' => [
                'code' => 'DEHAM',
                'name' => 'Port of Hamburg',
                'city' => 'Hamburg',
                'country' => 'Germany',
                'latitude' => 53.5461000,
                'longitude' => 9.9661000,
            ],
            'bremerhaven' => [
                'code' => 'DEBRV',
                'name' => 'Port of Bremerhaven',
                'city' => 'Bremerhaven',
                'country' => 'Germany',
                'latitude' => 53.5396000,
                'longitude' => 8.5809000,
            ],
            'felixstowe' => [
                'code' => 'GBFXT',
                'name' => 'Port of Felixstowe',
                'city' => 'Felixstowe',
                'country' => 'United Kingdom',
                'latitude' => 51.9542000,
                'longitude' => 1.3511000,
            ],
            'southampton' => [
                'code' => 'GBSOU',
                'name' => 'Port of Southampton',
                'city' => 'Southampton',
                'country' => 'United Kingdom',
                'latitude' => 50.9097000,
                'longitude' => -1.4044000,
            ],
            'leHavre' => [
                'code' => 'FRLEH',
                'name' => 'Port of Le Havre (HAROPA)',
                'city' => 'Le Havre',
                'country' => 'France',
                'latitude' => 49.4944000,
                'longitude' => 0.1079000,
            ],
            'marseille' => [
                'code' => 'FRMRS',
                'name' => 'Port of Marseille Fos',
                'city' => 'Marseille',
                'country' => 'France',
                'latitude' => 43.3438000,
                'longitude' => 5.3658000,
            ],
            'genoa' => [
                'code' => 'ITGOA',
                'name' => 'Port of Genoa',
                'city' => 'Genoa',
                'country' => 'Italy',
                'latitude' => 44.4056000,
                'longitude' => 8.9463000,
            ],
            'valencia' => [
                'code' => 'ESVLC',
                'name' => 'Port of Valencia',
                'city' => 'Valencia',
                'country' => 'Spain',
                'latitude' => 39.4497000,
                'longitude' => -0.3169000,
            ],
            'algeciras' => [
                'code' => 'ESALG',
                'name' => 'Port of Algeciras',
                'city' => 'Algeciras',
                'country' => 'Spain',
                'latitude' => 36.1408000,
                'longitude' => -5.4561000,
            ],
            'piraeus' => [
                'code' => 'GRPIR',
                'name' => 'Port of Piraeus',
                'city' => 'Athens',
                'country' => 'Greece',
                'latitude' => 37.9422000,
                'longitude' => 23.6467000,
            ],
            'gothenburg' => [
                'code' => 'SEGOT',
                'name' => 'Port of Gothenburg',
                'city' => 'Gothenburg',
                'country' => 'Sweden',
                'latitude' => 57.7089000,
                'longitude' => 11.9746000,
            ],
            'gdansk' => [
                'code' => 'PLGDN',
                'name' => 'Port of Gdansk (Baltic Hub)',
                'city' => 'Gdansk',
                'country' => 'Poland',
                'latitude' => 54.4025000,
                'longitude' => 18.6956000,
            ],

            // --- Amerika Utara & Amerika Selatan (69–85) ---
            'losAngeles' => [
                'code' => 'USLAX',
                'name' => 'Port of Los Angeles',
                'city' => 'Los Angeles',
                'country' => 'United States',
                'latitude' => 33.7405000,
                'longitude' => -118.2720000,
            ],
            'longBeach' => [
                'code' => 'USLGB',
                'name' => 'Port of Long Beach',
                'city' => 'Long Beach',
                'country' => 'United States',
                'latitude' => 33.7542000,
                'longitude' => -118.2165000,
            ],
            'newYork' => [
                'code' => 'USNYC',
                'name' => 'Port of New York and New Jersey',
                'city' => 'New York',
                'country' => 'United States',
                'latitude' => 40.6681000,
                'longitude' => -74.0451000,
            ],
            'savannah' => [
                'code' => 'USSAV',
                'name' => 'Port of Savannah',
                'city' => 'Savannah',
                'country' => 'United States',
                'latitude' => 32.1264000,
                'longitude' => -81.1475000,
            ],
            'houston' => [
                'code' => 'USHOU',
                'name' => 'Port of Houston',
                'city' => 'Houston',
                'country' => 'United States',
                'latitude' => 29.7344000,
                'longitude' => -95.0194000,
            ],
            'seattle' => [
                'code' => 'USSEA',
                'name' => 'Seaport Alliance Seattle',
                'city' => 'Seattle',
                'country' => 'United States',
                'latitude' => 47.5801000,
                'longitude' => -122.3534000,
            ],
            'tacoma' => [
                'code' => 'USTIW',
                'name' => 'Seaport Alliance Tacoma',
                'city' => 'Tacoma',
                'country' => 'United States',
                'latitude' => 47.2667000,
                'longitude' => -122.4167000,
            ],
            'miami' => [
                'code' => 'USMIA',
                'name' => 'PortMiami',
                'city' => 'Miami',
                'country' => 'United States',
                'latitude' => 25.7743000,
                'longitude' => -80.1717000,
            ],
            'vancouver' => [
                'code' => 'CAVAN',
                'name' => 'Port of Vancouver',
                'city' => 'Vancouver',
                'country' => 'Canada',
                'latitude' => 49.2897000,
                'longitude' => -123.1089000,
            ],
            'montreal' => [
                'code' => 'CAMTR',
                'name' => 'Port of Montreal',
                'city' => 'Montreal',
                'country' => 'Canada',
                'latitude' => 45.5455000,
                'longitude' => -73.5240000,
            ],
            'manzanillo' => [
                'code' => 'MXZLO',
                'name' => 'Port of Manzanillo',
                'city' => 'Manzanillo',
                'country' => 'Mexico',
                'latitude' => 19.0522000,
                'longitude' => -104.3158000,
            ],
            'colon' => [
                'code' => 'PACOL',
                'name' => 'Manzanillo International Terminal (Colon)',
                'city' => 'Colon',
                'country' => 'Panama',
                'latitude' => 9.3547000,
                'longitude' => -79.8836000,
            ],
            'cartagena' => [
                'code' => 'COCTG',
                'name' => 'Port of Cartagena',
                'city' => 'Cartagena',
                'country' => 'Colombia',
                'latitude' => 10.3910000,
                'longitude' => -75.5444000,
            ],
            'callao' => [
                'code' => 'PECLL',
                'name' => 'Port of Callao',
                'city' => 'Lima',
                'country' => 'Peru',
                'latitude' => -12.0500000,
                'longitude' => -77.1500000,
            ],
            'valparaiso' => [
                'code' => 'CLVAP',
                'name' => 'Port of Valparaiso',
                'city' => 'Valparaiso',
                'country' => 'Chile',
                'latitude' => -33.0472000,
                'longitude' => -71.6127000,
            ],
            'santos' => [
                'code' => 'BRSSZ',
                'name' => 'Port of Santos',
                'city' => 'Santos',
                'country' => 'Brazil',
                'latitude' => -23.9539000,
                'longitude' => -46.3336000,
            ],
            'buenosAires' => [
                'code' => 'ARBUE',
                'name' => 'Port of Buenos Aires',
                'city' => 'Buenos Aires',
                'country' => 'Argentina',
                'latitude' => -34.5889000,
                'longitude' => -58.3689000,
            ],

            // --- Afrika & Oseania (86–100) ---
            'durban' => [
                'code' => 'ZADUR',
                'name' => 'Port of Durban',
                'city' => 'Durban',
                'country' => 'South Africa',
                'latitude' => -29.8722000,
                'longitude' => 31.0289000,
            ],
            'capeTown' => [
                'code' => 'ZACPT',
                'name' => 'Port of Cape Town',
                'city' => 'Cape Town',
                'country' => 'South Africa',
                'latitude' => -33.9046000,
                'longitude' => 18.4319000,
            ],
            'tangerMed' => [
                'code' => 'MAPTM',
                'name' => 'Tanger Med Port',
                'city' => 'Tangier',
                'country' => 'Morocco',
                'latitude' => 35.8878000,
                'longitude' => -5.5036000,
            ],
            'portSaid' => [
                'code' => 'EGPSD',
                'name' => 'Port Said (Suez Canal East)',
                'city' => 'Port Said',
                'country' => 'Egypt',
                'latitude' => 31.2653000,
                'longitude' => 32.3019000,
            ],
            'mombasa' => [
                'code' => 'KEMBA',
                'name' => 'Port of Mombasa (Kilindini)',
                'city' => 'Mombasa',
                'country' => 'Kenya',
                'latitude' => -4.0600000,
                'longitude' => 39.6600000,
            ],
            'lagos' => [
                'code' => 'NGLOS',
                'name' => 'Lekki Deep Sea Port',
                'city' => 'Lagos',
                'country' => 'Nigeria',
                'latitude' => 6.4306000,
                'longitude' => 3.7703000,
            ],
            'darEsSalaam' => [
                'code' => 'TZDAR',
                'name' => 'Port of Dar es Salaam',
                'city' => 'Dar es Salaam',
                'country' => 'Tanzania',
                'latitude' => -6.8235000,
                'longitude' => 39.2935000,
            ],
            'abidjan' => [
                'code' => 'CIABJ',
                'name' => 'Autonomous Port of Abidjan',
                'city' => 'Abidjan',
                'country' => 'Cote d\'Ivoire',
                'latitude' => 5.2767000,
                'longitude' => -3.9922000,
            ],
            'sydney' => [
                'code' => 'AUSYD',
                'name' => 'Port Botany Sydney',
                'city' => 'Sydney',
                'country' => 'Australia',
                'latitude' => -33.9722000,
                'longitude' => 151.2241000,
            ],
            'melbourne' => [
                'code' => 'AUMEL',
                'name' => 'Port of Melbourne',
                'city' => 'Melbourne',
                'country' => 'Australia',
                'latitude' => -37.8267000,
                'longitude' => 144.9156000,
            ],
            'brisbane' => [
                'code' => 'AUBNE',
                'name' => 'Port of Brisbane',
                'city' => 'Brisbane',
                'country' => 'Australia',
                'latitude' => -27.3833000,
                'longitude' => 153.1667000,
            ],
            'fremantle' => [
                'code' => 'AUFRE',
                'name' => 'Port of Fremantle',
                'city' => 'Perth',
                'country' => 'Australia',
                'latitude' => -32.0542000,
                'longitude' => 115.7439000,
            ],
            'auckland' => [
                'code' => 'NZAKL',
                'name' => 'Port of Auckland',
                'city' => 'Auckland',
                'country' => 'New Zealand',
                'latitude' => -36.8406000,
                'longitude' => 174.7736000,
            ],
            'tauranga' => [
                'code' => 'NZTRG',
                'name' => 'Port of Tauranga',
                'city' => 'Tauranga',
                'country' => 'New Zealand',
                'latitude' => -37.6578000,
                'longitude' => 176.1856000,
            ],
            'suva' => [
                'code' => 'FJSUV',
                'name' => 'Port of Suva',
                'city' => 'Suva',
                'country' => 'Fiji',
                'latitude' => -18.1416000,
                'longitude' => 178.4419000,
            ],
        ];

        $loc = [];
        foreach ($ports as $key => $portData) {
            $loc[$key] = Location::updateOrCreate(
                ['code' => $portData['code']],
                $portData
            );
        }

        $now = Carbon::now();

        // 4. Shipment Group A (Indonesia -> Europe via Singapore | Threshold: 12h | Customs Hold Delay)
        $shipmentA = Shipment::updateOrCreate(
            ['shipment_number' => 'CT-2026-A001'],
            [
                'customer_id' => $customer1->id,
                'carrier_id' => $maersk->id,
                'origin_location_id' => $loc['jakarta']->id,
                'destination_location_id' => $loc['rotterdam']->id,
                'group_code' => 'A',
                'delay_threshold_hours' => 12,
                'cargo_type' => 'Coffee & Spices Container (20ft)',
                'weight_kg' => 14500.00,
                'volume_m3' => 28.500,
                'expected_departure_at' => $now->copy()->subDays(12),
                'expected_arrival_at' => $now->copy()->addDays(2),
                'estimated_arrival_at' => $now->copy()->addDays(2)->addHours(16),
                'current_status' => 'IN_TRANSIT',
                'is_delayed' => true,
                'delay_hours' => 16,
                'delay_severity' => 'MEDIUM',
                'delay_reason' => 'Estimated arrival exceeds Group A 12h threshold by 16h due to customs inspection.',
            ]
        );

        ShipmentItem::updateOrCreate(
            ['shipment_id' => $shipmentA->id, 'sku' => 'GAYO-ARABICA-01'],
            [
                'item_name' => 'Aceh Gayo Green Coffee Beans (250 Bags)',
                'quantity' => 250,
                'weight_kg' => 14500.00,
                'declared_value' => 58000.00,
            ]
        );

        if ($shipmentA->trackingEvents()->count() === 0) {
            TrackingEvent::create([
                'shipment_id' => $shipmentA->id,
                'location_id' => $loc['jakarta']->id,
                'created_by' => $operator->id,
                'status' => 'CREATED',
                'event_type' => 'NORMAL',
                'location_label' => 'Port of Tanjung Priok, Jakarta',
                'latitude' => $loc['jakarta']->latitude,
                'longitude' => $loc['jakarta']->longitude,
                'description' => 'Shipment order created and export manifest registered.',
                'occurred_at' => $now->copy()->subDays(13),
            ]);

            TrackingEvent::create([
                'shipment_id' => $shipmentA->id,
                'location_id' => $loc['singapore']->id,
                'created_by' => $operator->id,
                'status' => 'IN_TRANSIT',
                'event_type' => 'EXCEPTION',
                'location_label' => 'PSA Transshipment Hub, Singapore',
                'latitude' => $loc['singapore']->latitude,
                'longitude' => $loc['singapore']->longitude,
                'description' => 'Container held for secondary customs documentation clearance.',
                'occurred_at' => $now->copy()->subDays(5),
            ]);
        }

        if ($shipmentA->exceptions()->count() === 0) {
            ShipmentException::create([
                'shipment_id' => $shipmentA->id,
                'reported_by' => $operator->id,
                'exception_type' => 'CUSTOMS_HOLD',
                'severity' => 'MEDIUM',
                'status' => 'INVESTIGATING',
                'notes' => 'Phytosanitary certificate verification requested at transshipment port.',
                'detected_at' => $now->copy()->subDays(5),
            ]);
        }

        // 5. Shipment Group B (Asia -> North America | Threshold: 18h | Weather Delay)
        $shipmentB = Shipment::updateOrCreate(
            ['shipment_number' => 'CT-2026-B001'],
            [
                'customer_id' => $customer2->id,
                'carrier_id' => $msc->id,
                'origin_location_id' => $loc['singapore']->id,
                'destination_location_id' => $loc['losAngeles']->id,
                'group_code' => 'B',
                'delay_threshold_hours' => 18,
                'cargo_type' => 'Electronics & Semiconductor Parts',
                'weight_kg' => 9800.00,
                'volume_m3' => 19.200,
                'expected_departure_at' => $now->copy()->subDays(10),
                'expected_arrival_at' => $now->copy()->addDays(4),
                'estimated_arrival_at' => $now->copy()->addDays(5)->addHours(4),
                'current_status' => 'IN_TRANSIT',
                'is_delayed' => true,
                'delay_hours' => 28,
                'delay_severity' => 'HIGH',
                'delay_reason' => 'Estimated arrival exceeds Group B 18h threshold by 28h due to North Pacific storm rerouting.',
            ]
        );

        ShipmentItem::updateOrCreate(
            ['shipment_id' => $shipmentB->id, 'sku' => 'ELEC-PCB-88'],
            [
                'item_name' => 'Industrial Control Boards (80 Pallets)',
                'quantity' => 80,
                'weight_kg' => 9800.00,
                'declared_value' => 142000.00,
            ]
        );

        if ($shipmentB->trackingEvents()->count() === 0) {
            TrackingEvent::create([
                'shipment_id' => $shipmentB->id,
                'location_id' => $loc['singapore']->id,
                'created_by' => $operator->id,
                'status' => 'PICKED_UP',
                'event_type' => 'NORMAL',
                'location_label' => 'Port of Singapore (PSA)',
                'latitude' => $loc['singapore']->latitude,
                'longitude' => $loc['singapore']->longitude,
                'description' => 'Loaded onto trans-Pacific container vessel MSC Aurora.',
                'occurred_at' => $now->copy()->subDays(10),
            ]);

            TrackingEvent::create([
                'shipment_id' => $shipmentB->id,
                'location_id' => null,
                'created_by' => $operator->id,
                'status' => 'IN_TRANSIT',
                'event_type' => 'EXCEPTION',
                'location_label' => 'North Pacific Ocean Waypoint',
                'latitude' => 28.4500000,
                'longitude' => 165.3000000,
                'description' => 'Vessel reduced speed and adjusted course due to severe storm front.',
                'occurred_at' => $now->copy()->subDays(2),
            ]);
        }

        if ($shipmentB->exceptions()->count() === 0) {
            ShipmentException::create([
                'shipment_id' => $shipmentB->id,
                'reported_by' => $operator->id,
                'exception_type' => 'WEATHER_DELAY',
                'severity' => 'HIGH',
                'status' => 'OPEN',
                'notes' => 'Heavy swell conditions in North Pacific corridor.',
                'detected_at' => $now->copy()->subDays(2),
            ]);
        }

        // 6. Shipment Group C (Indonesia -> Australia | Threshold: 24h | On-Time Delivered + Resolved Exception)
        $shipmentC = Shipment::updateOrCreate(
            ['shipment_number' => 'CT-2026-C001'],
            [
                'customer_id' => $customer1->id,
                'carrier_id' => $pil->id,
                'origin_location_id' => $loc['jakarta']->id,
                'destination_location_id' => $loc['sydney']->id,
                'group_code' => 'C',
                'delay_threshold_hours' => 24,
                'cargo_type' => 'Textiles & Apparel Garments',
                'weight_kg' => 7200.00,
                'volume_m3' => 22.000,
                'expected_departure_at' => $now->copy()->subDays(9),
                'expected_arrival_at' => $now->copy()->subDays(1),
                'estimated_arrival_at' => $now->copy()->subDays(1)->addHours(6),
                'delivered_at' => $now->copy()->subDays(1)->addHours(5),
                'current_status' => 'DELIVERED',
                'is_delayed' => false,
                'delay_hours' => 0,
                'delay_severity' => null,
                'delay_reason' => null,
            ]
        );

        ShipmentItem::updateOrCreate(
            ['shipment_id' => $shipmentC->id, 'sku' => 'TEX-BATIK-12'],
            [
                'item_name' => 'Export Cotton Garment Cartons',
                'quantity' => 180,
                'weight_kg' => 7200.00,
                'declared_value' => 41500.00,
            ]
        );

        if ($shipmentC->trackingEvents()->count() === 0) {
            TrackingEvent::create([
                'shipment_id' => $shipmentC->id,
                'location_id' => $loc['jakarta']->id,
                'created_by' => $operator->id,
                'status' => 'BOOKED',
                'event_type' => 'NORMAL',
                'location_label' => 'Port of Tanjung Priok, Jakarta',
                'latitude' => $loc['jakarta']->latitude,
                'longitude' => $loc['jakarta']->longitude,
                'description' => 'Vessel slot confirmed with PIL.',
                'occurred_at' => $now->copy()->subDays(9),
            ]);

            TrackingEvent::create([
                'shipment_id' => $shipmentC->id,
                'location_id' => $loc['sydney']->id,
                'created_by' => $operator->id,
                'status' => 'DELIVERED',
                'event_type' => 'NORMAL',
                'location_label' => 'Port Botany Sydney, Australia',
                'latitude' => $loc['sydney']->latitude,
                'longitude' => $loc['sydney']->longitude,
                'description' => 'Cargo cleared and delivered to consignee warehouse within Group C 24h SLA.',
                'occurred_at' => $now->copy()->subDays(1)->addHours(5),
            ]);
        }

        if ($shipmentC->exceptions()->count() === 0) {
            ShipmentException::create([
                'shipment_id' => $shipmentC->id,
                'reported_by' => $operator->id,
                'resolved_by' => $admin->id,
                'exception_type' => 'DOCUMENTATION_ISSUE',
                'severity' => 'LOW',
                'status' => 'RESOLVED',
                'notes' => 'Bill of Lading consignee tax ID corrected prior to arrival.',
                'detected_at' => $now->copy()->subDays(6),
                'resolved_at' => $now->copy()->subDays(5),
            ]);
        }

        // 7. Initial Notifications & Audit Logs
        Notification::firstOrCreate(
            [
                'user_id' => $customer1->id,
                'shipment_id' => $shipmentA->id,
                'title' => 'Delay Alert: CT-2026-A001 (Group A)',
            ],
            [
                'message' => 'Shipment CT-2026-A001 exceeded the 12h delay threshold due to CUSTOMS_HOLD in Singapore.',
                'type' => 'WARNING',
            ]
        );

        Notification::firstOrCreate(
            [
                'user_id' => $customer2->id,
                'shipment_id' => $shipmentB->id,
                'title' => 'High Severity Weather Delay: CT-2026-B001',
            ],
            [
                'message' => 'Shipment CT-2026-B001 is delayed by 28h (Group B threshold: 18h) due to WEATHER_DELAY.',
                'type' => 'DANGER',
            ]
        );

        AuditLog::firstOrCreate(
            [
                'action' => 'SEED_DEMO_DATA',
                'entity_type' => 'Shipment',
                'entity_id' => $shipmentA->id,
            ],
            [
                'actor_id' => $admin->id,
                'metadata' => ['groups' => ['A (12h)', 'B (18h)', 'C (24h)'], 'mode' => 'seaport'],
                'created_at' => $now,
            ]
        );
    }
}