<?php

namespace App\Policies;

use App\Models\LegalEntity;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Auth;

class LegalEntityPolicy
{
   /**
    * Determine if the user has access to the legal entity
    */
   public function access(User $user, LegalEntity $currentEntity): Response
   {
       $legalEntitiyIds = $user->employees->pluck('legal_entity_id')->toArray();

       $shouldAllow = in_array($currentEntity->id, $legalEntitiyIds);

       if (!$shouldAllow) {
           return Response::denyWithStatus(404);
       }

       app()->bind(LegalEntity::class, fn () => Auth::user()->legalEntity);
       app()->alias(LegalEntity::class, 'legalEntity');
       setPermissionsTeamId($currentEntity->id);
       return Response::allow();
   }
}
