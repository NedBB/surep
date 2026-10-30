<?php

namespace App\Livewire;

use Livewire\Component;

class NigeriaMap extends Component
{
    public $states = [];
    public $hoveredState = null;

    public function mount($states)
    {
        $this->states = collect($states)->keyBy('code')->toArray();
    }

    public function selectState($stateId)
    {
        $this->dispatch('state-selected', stateId: $stateId);
    }

    public function render()
    {
        return view('livewire.nigeria-map');
    }
}
