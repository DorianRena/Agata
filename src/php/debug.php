<?php

class Debug
{
    private $mode;
    const FLAG = 'uNs3Cur3 dES1R1ALi2a7Ion';

    public function __construct(string $mode)
    {
        $this->mode = $mode;
    }
    // Sérialisation :
    public function __serialize(): array
    {
        return ['mode' => $this->mode];
    }
    //    // Désérialisation :
    public function __unserialize(array $data)
    {
        $this->mode = $data['mode'] ?? 'unknown';

    }

    // Pour echo $instance
    public function __toString(): string
    {
        if ($this->mode === 'flag') {
            return self::FLAG;
        }
        return 'Debug';
    }
}