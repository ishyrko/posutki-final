<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

final class GeocoderPlaceExtractor
{
    /**
     * @param array<string, mixed> $data
     */
    public function extract(array $data): GeocoderPlaces
    {
        $adminDistrict = null;
        $microdistricts = [];
        $complexes = [];

        $members = $data['response']['GeoObjectCollection']['featureMember'] ?? null;
        if (!is_array($members)) {
            return new GeocoderPlaces();
        }

        foreach ($members as $member) {
            if (!is_array($member)) {
                continue;
            }

            $geo = $member['GeoObject'] ?? [];
            $meta = is_array($geo['metaDataProperty']['GeocoderMetaData'] ?? null)
                ? $geo['metaDataProperty']['GeocoderMetaData']
                : [];
            $kind = $meta['kind'] ?? null;
            $objectName = is_string($geo['name'] ?? null) ? trim($geo['name']) : '';
            $components = is_array($meta['Address']['Components'] ?? null) ? $meta['Address']['Components'] : [];

            $candidates = $this->collectCandidates($objectName, is_string($kind) ? $kind : null, $components);

            foreach ($candidates as $candidate) {
                $name = $candidate['name'];
                if ($this->isNumberedPart($name)) {
                    continue;
                }

                if ($this->isResidentialComplexName($name)) {
                    $complexes[$name] = true;
                    continue;
                }

                if ($this->isMicrodistrictName($name)) {
                    $microdistricts[$name] = true;
                    continue;
                }

                if ($this->isAdminDistrictName($name) && ($kind === 'district' || $candidate['fromDistrictKind'])) {
                    $adminDistrict = $name;
                }
            }
        }

        return new GeocoderPlaces(
            adminDistrictOfficialName: $adminDistrict,
            microdistrictOfficialNames: $this->sortMicrodistrictNames(array_keys($microdistricts)),
            residentialComplexOfficialNames: array_keys($complexes),
        );
    }

    /**
     * @param list<array{name: string, kind: ?string, fromDistrictKind: bool}> $components
     * @return list<array{name: string, fromDistrictKind: bool}>
     */
    private function collectCandidates(string $objectName, ?string $objectKind, array $components): array
    {
        $candidates = [];

        foreach ($components as $component) {
            if (!is_array($component)) {
                continue;
            }
            $name = isset($component['name']) && is_string($component['name']) ? trim($component['name']) : '';
            if ($name === '') {
                continue;
            }
            $candidates[] = [
                'name' => $name,
                'fromDistrictKind' => ($component['kind'] ?? null) === 'district',
            ];
        }

        if ($objectName !== '') {
            $candidates[] = [
                'name' => $objectName,
                'fromDistrictKind' => $objectKind === 'district',
            ];
        }

        return $candidates;
    }

    public function isNumberedPart(string $name): bool
    {
        if (preg_match('/-\d+$/u', $name) === 1) {
            return true;
        }
        if (preg_match('/№\s*\d+$/u', $name) === 1) {
            return true;
        }
        if (preg_match('/^\d+-й\s+квартал$/ui', $name) === 1) {
            return true;
        }

        return false;
    }

    public function isAdminDistrictName(string $name): bool
    {
        $lower = mb_strtolower($name);

        return str_ends_with($lower, ' район')
            || str_contains($lower, 'исторический район');
    }

    public function isMicrodistrictName(string $name): bool
    {
        $lower = mb_strtolower($name);

        if ($this->isAdminDistrictName($name)) {
            return false;
        }

        if ($this->isResidentialComplexName($name)) {
            return false;
        }

        if (
            str_contains($lower, 'микрорайон')
            || str_contains($lower, 'мкр.')
            || preg_match('/\bмкрн?\b/u', $lower) === 1
        ) {
            return true;
        }

        if (str_contains($lower, 'городок')) {
            return true;
        }

        return false;
    }

    public function isResidentialComplexName(string $name): bool
    {
        $lower = mb_strtolower($name);

        if (
            str_contains($lower, 'жилой комплекс')
            || str_contains($lower, 'жилой квартал')
            || preg_match('/\bжк\b/ui', $name) === 1
            || str_starts_with($lower, 'жк')
        ) {
            return true;
        }

        if (str_contains($lower, 'минск-мир') || str_contains($lower, 'минск мир')) {
            return true;
        }

        if (str_contains($lower, 'экспериментальный многофункциональный комплекс')) {
            return true;
        }

        return false;
    }

    /**
     * Prefer parent microdistrict names (without numeric suffix) and stable ordering.
     *
     * @param list<string> $names
     * @return list<string>
     */
    private function sortMicrodistrictNames(array $names): array
    {
        usort($names, static fn (string $a, string $b): int => strlen($a) <=> strlen($b));

        return array_values($names);
    }
}
