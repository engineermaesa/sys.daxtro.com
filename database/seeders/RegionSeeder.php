<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        $branchIds = DB::table('ref_branches')->pluck('id', 'name');

        $data = json_decode(include __DIR__.'/data/regions.php', TRUE);

        $regionalIds = [];
        $provinceIds = [];

        foreach ($data as $row) {
            $regionalName = $row['Regional'];
            $provinceName = $row['Provinsi'];
            $branchName   = $row['Branch'];

            if (!isset($regionalIds[$regionalName])) {
                $regionalIds[$regionalName] = DB::table('ref_regionals')->insertGetId([
                    'name' => $regionalName,
                ]);
            }

            if (!isset($provinceIds[$provinceName])) {
                $provinceIds[$provinceName] = DB::table('ref_provinces')->insertGetId([
                    'regional_id' => $regionalIds[$regionalName],
                    'name'        => $provinceName,
                ]);
            }

            DB::table('ref_regions')->updateOrInsert(
                [
                    'name'      => $row['Region'],
                    'branch_id' => $branchIds[$branchName] ?? null,
                ],
                [
                    'branch_id'   => $branchIds[$branchName] ?? null,
                    'regional_id' => $regionalIds[$regionalName],
                    'province_id' => $provinceIds[$provinceName],
                    'name'        => $row['Region'],
                    'code'        => Str::slug($row['Region']),
                ]
            );
        }
    }
}
