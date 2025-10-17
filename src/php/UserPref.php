<?php

class UserPref
{
    private $preferences = [];

    public function __construct(array $prefs = [])
    {
        // Initialisation possible avec un tableau de préférences
        $this->preferences = $prefs;
    }

    // Sérialisation : on ne sauvegarde que les préférences
    public function __serialize(): array
    {
        return ['preferences' => $this->preferences];
    }

    // Désérialisation : on restaure le tableau de préférences
    public function __unserialize(array $data)
    {
        $this->preferences = $data['preferences'] ?? [];
    }

    // Récupérer une préférence
    public function get(string $key, $default = null)
    {
        return $this->preferences[$key] ?? $default;
    }

    // Mettre à jour une préférence
    public function set(string $key, $value)
    {
        $this->preferences[$key] = $value;
    }

    // Affichage simple de toutes les préférences
    public function __toString(): string
    {
        return json_encode($this->preferences, JSON_PRETTY_PRINT);
    }
}