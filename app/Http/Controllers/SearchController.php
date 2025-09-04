<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    public function customers(Request $request): JsonResponse
    {
        $q = trim((string) $request->get('q', ''));
        $type = $request->get('type'); // optional: 'company' | 'contact' | null
        $limit = (int) ($request->get('limit', 10));
        $limit = max(1, min(25, $limit));
        if ($q === '') {
            return response()->json(['companies' => [], 'contacts' => []]);
        }

        $companies = collect();
        if (!$type || $type === 'company') {
            $companies = Company::where('name', 'like', "%$q%")
                ->limit($limit)
                ->get(['id','name']);
        }

        $contacts = collect();
        if (!$type || $type === 'contact') {
            $contacts = Contact::where(function($w) use ($q) {
                    $w->where('first_name','like',"%$q%")
                      ->orWhere('last_name','like',"%$q%")
                      ->orWhere('email','like',"%$q%");
                })
                ->limit($limit)
                ->get(['id','first_name','last_name','email']);
        }

        $contacts = $contacts->map(function($c){
            return [
                'id' => $c->id,
                'name' => trim(($c->first_name.' '.$c->last_name)) ?: ($c->email ?? ('Kontakt #'.$c->id)),
                'email' => $c->email,
            ];
        });

        return response()->json([
            'companies' => $companies,
            'contacts' => $contacts,
        ]);
    }

    public function taskables(Request $request): JsonResponse
    {
        $q = trim((string) $request->get('q', ''));
        $type = $request->get('type'); // optional: company|contact|lead|opportunity|project|null
        $limit = (int) ($request->get('limit', 10));
        $limit = max(1, min(25, $limit));
        if ($q === '') {
            return response()->json(['companies' => [], 'contacts' => [], 'leads' => [], 'opportunities' => [], 'projects' => []]);
        }

        $companies = collect();
        if (!$type || $type === 'company') {
            $companies = Company::where('name', 'like', "%$q%")
                ->limit($limit)
                ->get(['id','name']);
        }

        $contacts = collect();
        if (!$type || $type === 'contact') {
            $contacts = Contact::where(function($w) use ($q) {
                    $w->where('first_name','like',"%$q%")
                      ->orWhere('last_name','like',"%$q%")
                      ->orWhere('email','like',"%$q%");
                })
                ->limit($limit)
                ->get(['id','first_name','last_name','email']);
        }

        $leads = collect();
        if (!$type || $type === 'lead') {
            $leads = Lead::where(function($w) use ($q){
                    $w->where('company_name','like',"%$q%")
                      ->orWhere('contact_name','like',"%$q%")
                      ->orWhere('email','like',"%$q%");
                })
                ->limit($limit)
                ->get(['id','company_name','contact_name','email']);
        }

        $opps = collect();
        if (!$type || $type === 'opportunity') {
            $opps = Opportunity::where('name','like',"%$q%")
                ->limit($limit)
                ->get(['id','name']);
        }

        $projects = collect();
        if (!$type || $type === 'project') {
            $projects = Project::where('name','like',"%$q%")
                ->limit($limit)
                ->get(['id','name']);
        }

        $contacts = $contacts->map(fn($c)=>[
            'id' => $c->id,
            'name' => trim(($c->first_name.' '.$c->last_name)) ?: ($c->email ?? ('Kontakt #'.$c->id)),
            'email' => $c->email,
        ]);
        $leads = $leads->map(fn($l)=>[
            'id' => $l->id,
            'name' => $l->company_name ?: ($l->contact_name ?: ('Lead #'.$l->id)),
            'email' => $l->email,
        ]);

        return response()->json([
            'companies' => $companies,
            'contacts' => $contacts,
            'leads' => $leads,
            'opportunities' => $opps,
            'projects' => $projects,
        ]);
    }
}
