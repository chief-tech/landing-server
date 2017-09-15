<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProviderAccount extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'provider_id',
        'stripe_acct_id',
        'stripe_sk_key',
        'stripe_pk_key',
        'country',
        'city',
        'address',
        'postal_code',
        'state',
        'DOB',
        'ssn_last_4',
        'type',
        'tos_acceptance_date',
        'tos_acceptance_ip',
        'personal_id_no',
        'verification_document',
        'bank_account_holder_name',
        'bank_account_holder_type',
        'bank_routing_number',
        'bank_account_number',
        'status'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at'
    ];
}
