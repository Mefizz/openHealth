<?php

namespace App\View\Components\Forms;

use Illuminate\View\Component;

class SignatureModal extends Component
{
    public array $certificateAuthorities;
    public string $knedp;
    public mixed $keyContainer;
    public string $password;

    public function __construct(
        string $modalId = 'showSignModal',
        string $submitMethod = 'submitForApproval',
        array $certificateAuthorities = [],
        string $knedp = '',
               $keyContainer = null,
        string $password = ''
    ) {
        $this->modalId = $modalId;
        $this->submitMethod = $submitMethod;
        $this->certificateAuthorities = $certificateAuthorities;
        $this->knedp = $knedp;
        $this->keyContainer = $keyContainer;
        $this->password = $password;
    }


    public function render()
    {
        return view('components.forms.signature-modal');
    }
}
