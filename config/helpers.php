<?php

function formatar_data_hora(string $timestamp): string
{
    $data = new DateTime($timestamp);
    return $data->format('d/m/Y \à\s H:i');
}
