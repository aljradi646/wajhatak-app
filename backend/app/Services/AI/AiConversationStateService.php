<?php

namespace App\Services\AI;

use App\Models\AiConversation;
use Illuminate\Support\Facades\Schema;

class AiConversationStateService
{
    public function state(AiConversation $conversation): array
    {
        return is_array($conversation->context_state) ? $conversation->context_state : [];
    }

    public function updateState(AiConversation $conversation,array $lastFilters=[],?array $selectedProperty=null): void
    {
        if (!$conversation->exists) return;
        $state=$this->state($conversation);
        if($lastFilters!==[]) $state['active_search']=array_merge($state['active_search']??[],$lastFilters);
        if($selectedProperty!==null) $state['selected_property']=$selectedProperty;
        $conversation->context_state=$state;
        if(empty($conversation->title)||$conversation->title==='محادثة جديدة') $conversation->title=$this->generateTitle($lastFilters,$selectedProperty);
        $conversation->save();
    }

    public function setPendingAction(AiConversation $conversation,?string $tool,array $arguments=[]): void
    {
        if(!$conversation->exists) return;
        $state=$this->state($conversation);
        $state['pending_action']=$tool?['tool'=>$tool,'arguments'=>$arguments]:null;
        $conversation->context_state=$state;
        $conversation->save();
    }

    public function pendingAction(AiConversation $conversation): ?array
    {
        $pending=$this->state($conversation)['pending_action']??null;
        return is_array($pending)&&isset($pending['tool'])&&is_array($pending['arguments']??null)?$pending:null;
    }

    private function generateTitle(array $filters,?array $selectedProperty): string
    {
        if($selectedProperty) return 'استفسار عن '.($selectedProperty['title']??'عقار');
        $parts=[];
        $parts[]=$filters['property_type_name']??$filters['property_type']??'بحث عقاري';
        if(!empty($filters['city'])) $parts[]='في '.$filters['city'];
        return implode(' ',$parts);
    }
}