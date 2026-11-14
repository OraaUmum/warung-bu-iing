<?php
declare(strict_types=1);

function e(?string $nilai): string
{
    return htmlspecialchars($nilai ?? '', ENT_QUOTES, 'UTF-8');
}

function rupiah(int|float|string $nilai): string
{
    return 'Rp' . number_format((float) $nilai, 0, ',', '.');
}
