<?php
function isOn($key)
{
    $value = ini_get($key);

    return false != $value && 'off' !== $value;
}
echo isOn('date.timezone');
echo "</br>";
echo ini_get('date.timezone');
?>