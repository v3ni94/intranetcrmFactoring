<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $tasks = Task::with('assignee')->orderByRaw("status = 'erledigt'")->orderBy('due_date')->paginate(25);

        return view('tasks.index', compact('tasks'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'due_date' => 'nullable|date',
            'priority' => 'required|in:niedrig,normal,hoch',
        ]);

        $task = Task::create($data + [
            'tenant_id' => TenantContext::id(),
            'assignee_id' => $request->user()->id,
            'status' => 'offen',
        ]);

        AuditLogger::log('create', Task::class, $task->id, [], $task->toArray());

        return back()->with('status', 'Aufgabe angelegt.');
    }

    public function complete(Task $task)
    {
        $task->update(['status' => 'erledigt']);
        AuditLogger::log('update', Task::class, $task->id, [], ['status' => 'erledigt']);

        return back()->with('status', 'Aufgabe erledigt.');
    }
}
