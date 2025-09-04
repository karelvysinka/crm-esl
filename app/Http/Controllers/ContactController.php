<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $onlyAc = request()->boolean('ac');
        $qText = trim((string) request('q', ''));
        $status = request('status');

        $q = Contact::with('company')->orderByDesc('created_at');

        if ($onlyAc) {
            $q->whereNotNull('ac_id');
        }
        if ($status && in_array($status, ['active','inactive','lead','prospect'], true)) {
            $q->where('status', $status);
        }
        if ($qText !== '') {
            $q->where(function($qq) use ($qText) {
                $term = '%'.$qText.'%';
                $qq->where('first_name', 'like', $term)
                   ->orWhere('last_name', 'like', $term)
                   ->orWhere('email', 'like', $term)
                   ->orWhere('phone', 'like', $term)
                   ->orWhere('mobile', 'like', $term)
                   ->orWhereHas('company', function($qc) use ($term){
                       $qc->where('name', 'like', $term);
                   });
            });
        }

        // Paginate to prevent memory exhaustion on large datasets
        $contacts = $q->paginate(50)->withQueryString();
        return view('crm.contacts.index', compact('contacts', 'qText', 'status', 'onlyAc'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companies = Company::orderBy('name')->get();
        return view('crm.contacts.create', compact('companies'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'mobile' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive,lead,prospect',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'preferred_contact' => 'nullable|in:email,phone,mobile'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $contact = Contact::create(array_merge($request->all(), [
            'created_by' => 1 // Dummy user ID - replace with auth()->id() when auth is implemented
        ]));

        return redirect(url('/crm/contacts'))
            ->with('success', 'Kontakt byl úspěšně vytvořen.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Contact $contact)
    {
        $contact->load('company', 'opportunities', 'createdBy');
        return view('crm.contacts.show', compact('contact'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Contact $contact)
    {
        $companies = Company::orderBy('name')->get();
        return view('crm.contacts.edit', compact('contact', 'companies'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Contact $contact)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'mobile' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive,lead,prospect',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'preferred_contact' => 'nullable|in:email,phone,mobile'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $contact->update($request->all());

        return redirect(url('/crm/contacts/' . $contact->id))
            ->with('success', 'Kontakt byl úspěšně aktualizován.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Contact $contact)
    {
        $contact->delete();
        
        return redirect(url('/crm/contacts'))
            ->with('success', 'Kontakt byl úspěšně smazán.');
    }
}
