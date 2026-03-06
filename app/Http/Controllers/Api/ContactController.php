<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ContactsImport;
use Illuminate\Support\Facades\Storage;

class ContactController extends Controller
{
    /**
     * Display a listing of the contacts.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Contact::with('tags');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by tag
        if ($request->has('tag')) {
            $query->byTag($request->tag);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate
        $perPage = $request->get('per_page', 15);
        $contacts = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $contacts,
        ]);
    }

    /**
     * Store a newly created contact.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:contacts,phone',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string|max:1000',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,name',
        ]);

        $contact = Contact::create($validated);

        // Add tags if provided
        if (!empty($validated['tags'])) {
            foreach ($validated['tags'] as $tagName) {
                $tag = Tag::firstOrCreate(['name' => $tagName]);
                $contact->addTag($tag);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $contact->load('tags'),
            'message' => 'Contact created successfully',
        ], 201);
    }

    /**
     * Display the specified contact.
     */
    public function show(Contact $contact): JsonResponse
    {
        $contact->load(['tags', 'conversations', 'messages' => function ($query) {
            $query->latest()->limit(50);
        }]);

        return response()->json([
            'success' => true,
            'data' => $contact,
        ]);
    }

    /**
     * Update the specified contact.
     */
    public function update(Request $request, Contact $contact): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('contacts')->ignore($contact->id),
            ],
            'email' => 'nullable|email|max:255',
            'status' => ['sometimes', 'required', Rule::in(['active', 'inactive', 'blocked'])],
            'notes' => 'nullable|string|max:1000',
            'metadata' => 'nullable|array',
        ]);

        $contact->update($validated);

        return response()->json([
            'success' => true,
            'data' => $contact->load('tags'),
            'message' => 'Contact updated successfully',
        ]);
    }

    /**
     * Remove the specified contact.
     */
    public function destroy(Contact $contact): JsonResponse
    {
        $contact->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contact deleted successfully',
        ]);
    }

    /**
     * Import contacts from CSV file
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240', // Max 10MB
        ]);

        try {
            $file = $request->file('file');
            
            Excel::import(new ContactsImport, $file);

            return response()->json([
                'success' => true,
                'message' => 'Contacts imported successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to import contacts: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export contacts to CSV
     */
    public function export(Request $request): JsonResponse
    {
        $query = Contact::with('tags');

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('tag')) {
            $query->byTag($request->tag);
        }

        $contacts = $query->get();

        $csvData = [];
        $csvData[] = ['Name', 'Phone', 'Email', 'Status', 'Tags', 'Notes', 'Last Message'];

        foreach ($contacts as $contact) {
            $csvData[] = [
                $contact->name,
                $contact->phone,
                $contact->email,
                $contact->status,
                $contact->tags->pluck('name')->implode(', '),
                $contact->notes,
                $contact->last_message_at?->format('Y-m-d H:i:s'),
            ];
        }

        $filename = 'contacts_export_' . date('Y-m-d_H-i-s') . '.csv';
        $filepath = 'exports/' . $filename;
        
        // Create CSV content
        $csv = implode("\n", array_map(function ($row) {
            return implode(',', array_map(function ($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, $row));
        }, $csvData));

        Storage::put($filepath, $csv);

        return response()->json([
            'success' => true,
            'data' => [
                'download_url' => Storage::url($filepath),
                'filename' => $filename,
                'total_contacts' => $contacts->count(),
            ],
            'message' => 'Contacts exported successfully',
        ]);
    }

    /**
     * Add tags to a contact
     */
    public function addTags(Request $request, Contact $contact): JsonResponse
    {
        $validated = $request->validate([
            'tags' => 'required|array',
            'tags.*' => 'exists:tags,name',
        ]);

        foreach ($validated['tags'] as $tagName) {
            $tag = Tag::firstOrCreate(['name' => $tagName]);
            $contact->addTag($tag);
        }

        return response()->json([
            'success' => true,
            'data' => $contact->load('tags'),
            'message' => 'Tags added successfully',
        ]);
    }

    /**
     * Remove a tag from a contact
     */
    public function removeTag(Contact $contact, Tag $tag): JsonResponse
    {
        $contact->removeTag($tag);

        return response()->json([
            'success' => true,
            'data' => $contact->load('tags'),
            'message' => 'Tag removed successfully',
        ]);
    }

    /**
     * Get contact statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_contacts' => Contact::count(),
            'active_contacts' => Contact::active()->count(),
            'inactive_contacts' => Contact::where('status', 'inactive')->count(),
            'blocked_contacts' => Contact::where('status', 'blocked')->count(),
            'contacts_with_conversations' => Contact::whereHas('conversations')->count(),
            'recent_contacts' => Contact::where('created_at', '>=', now()->subDays(30))->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
