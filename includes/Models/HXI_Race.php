<?php

enum HXI_Race: int {
    case Hume      = 0;
    case Elvaan    = 1;
    case Tarutaru  = 2;
    case Mithra    = 3;
    case Galka     = 4;

    public function label(): string {
        return match($this) {
            self::Hume     => "Hume",
            self::Elvaan   => "Elvaan",
            self::Tarutaru => "Tarutaru",
            self::Mithra   => "Mithra",
            self::Galka    => "Galka",
        };
    }
}

?>
