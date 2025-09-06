<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $guarded = [];
    protected $casts   = ['meta'=>'array'];

    public function messages(): HasMany {
        return $this->hasMany(Message::class)->orderBy('id');
    }

    /** Conversations table variants supported:
     *  - user_one_id / user_two_id (we’ll prefer these)
     *  - or buyer_id / seller_id (fallback if present)
     */
    public function hasUser(int $userId): bool
    {
        if (isset($this->user_one_id, $this->user_two_id)) {
            return ((int)$this->user_one_id === $userId) || ((int)$this->user_two_id === $userId);
        }
        if (isset($this->buyer_id, $this->seller_id)) {
            return ((int)$this->buyer_id === $userId) || ((int)$this->seller_id === $userId);
        }
        return false;
    }

    public function otherParticipantId(int $me): ?int
    {
        if (!$this->hasUser($me)) return null;
        if (isset($this->user_one_id, $this->user_two_id)) {
            return (int)$this->user_one_id === $me ? (int)$this->user_two_id : (int)$this->user_one_id;
        }
        if (isset($this->buyer_id, $this->seller_id)) {
            return (int)$this->buyer_id === $me ? (int)$this->seller_id : (int)$this->buyer_id;
        }
        return null;
    }

    /** get or create a pair for two users */
    public static function pair(int $a, int $b): self
    {
        $u1 = min($a,$b); $u2 = max($a,$b);

        // prefer user_one_id/user_two_id
        $q = static::query()->where(function($w) use ($u1,$u2){
            $w->where('user_one_id',$u1)->where('user_two_id',$u2);
        });

        // fallback legacy buyer/seller (if columns exist)
        $tableCols = \Illuminate\Support\Facades\Schema::getColumnListing((new static)->getTable());
        if (in_array('buyer_id',$tableCols) && in_array('seller_id',$tableCols)) {
            $q->orWhere(function($w) use ($u1,$u2){
                $w->where('buyer_id',$u1)->where('seller_id',$u2);
            })->orWhere(function($w) use ($u1,$u2){
                $w->where('buyer_id',$u2)->where('seller_id',$u1);
            });
        }

        $conv = $q->first();
        if ($conv) return $conv;

        $data = ['user_one_id'=>$u1,'user_two_id'=>$u2];
        return static::create($data);
    }
}
