<?php
// ============================================================================
// app/Core/EventManager.php
// ============================================================================

namespace App\Core;

class EventManager {
    private array $listeners = [];

    /**
     * Listener für ein Event registrieren
     *
     * @param string $eventName Name des Events
     * @param callable $listener Callback-Funktion, die ausgeführt wird
     */
    public function on(string $eventName, callable $listener): void {
        $this->listeners[$eventName][] = $listener;
    }

    /**
     * Event feuern
     *
     * @param string $eventName Name des Events
     * @param mixed $data Daten, die an die Listener übergeben werden
     */
    public function emit(string $eventName, mixed $data = null): void {
        if(!empty($this->listeners[$eventName])) {
            foreach($this->listeners[$eventName] as $listener) {
                $listener($data);
            }
        }
    }

    /**
     * Einen Listener von einem Event entfernen
     *
     * @param string $eventName
     * @param callable $listener
     */
    public function off(string $eventName, callable $listener): void {
        if(!empty($this->listeners[$eventName])) {
            $this->listeners[$eventName] = array_filter(
                $this->listeners[$eventName],
                fn($l) => $l !== $listener
            );
        }
    }
}
