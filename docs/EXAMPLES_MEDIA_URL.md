# Examples: Using Media URL in Approval Histories

## Basic Usage

### PHP Controller

```php
use AsetKita\LaravelApprovalWorkflow\Services\ApprovalHandler;

class ApprovalController extends Controller
{
    public function showHistory($approvalId)
    {
        $handler = new ApprovalHandler(auth()->user()->company_id);
        $histories = $handler->getApprovalHistories($approvalId);
        
        return view('approval.history', compact('histories'));
    }
}
```

### Blade Template

```blade
<!-- resources/views/approval/history.blade.php -->
<div class="approval-history">
    <h2>Approval History</h2>
    
    @foreach($histories as $history)
        <div class="history-item">
            <div class="history-header">
                <strong>{{ $history['title'] }}</strong>
                <span class="badge badge-{{ $history['flag'] }}">
                    {{ ucfirst($history['flag']) }}
                </span>
            </div>
            
            <div class="history-body">
                <p><strong>By:</strong> {{ $history['user_name'] }}</p>
                <p><strong>Date:</strong> {{ date('Y-m-d H:i:s', $history['date_time']) }}</p>
                
                @if($history['notes'])
                    <p><strong>Notes:</strong> {{ $history['notes'] }}</p>
                @endif
                
                @if($history['media_url'])
                    <div class="attachment">
                        <strong>Attachment:</strong>
                        <a href="{{ $history['media_url'] }}" target="_blank" class="btn btn-sm btn-primary">
                            <i class="fa fa-download"></i> Download File
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @endforeach
</div>
```

## API Response

### Laravel API Controller

```php
use AsetKita\LaravelApprovalWorkflow\Services\ApprovalHandler;

class ApiApprovalController extends Controller
{
    public function getHistory($approvalId)
    {
        $handler = new ApprovalHandler(auth()->user()->company_id);
        $histories = $handler->getApprovalHistories($approvalId);
        
        return response()->json([
            'success' => true,
            'data' => $histories
        ]);
    }
}
```

### JSON Response Example

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "approval_id": 123,
      "flow_step_id": 1,
      "flow_step_name": "Manager Approval",
      "user_id": 5,
      "user_name": "John Doe",
      "user_email": "john@example.com",
      "title": "Approved by Manager",
      "flag": "approved",
      "notes": "Budget approved",
      "date_time": 1696752000,
      "media_url": "http://example.com/storage/1/approval-document.pdf"
    },
    {
      "id": 2,
      "approval_id": 123,
      "flow_step_id": 2,
      "flow_step_name": "Director Approval",
      "user_id": 3,
      "user_name": "Jane Smith",
      "user_email": "jane@example.com",
      "title": "Approved by Director",
      "flag": "approved",
      "notes": "Final approval granted",
      "date_time": 1696838400,
      "media_url": null
    }
  ]
}
```

## Livewire Component

### Component Class

```php
namespace App\Http\Livewire;

use Livewire\Component;
use AsetKita\LaravelApprovalWorkflow\Services\ApprovalHandler;

class ApprovalHistoryComponent extends Component
{
    public $approvalId;
    public $histories = [];
    
    public function mount($approvalId)
    {
        $this->approvalId = $approvalId;
        $this->loadHistories();
    }
    
    public function loadHistories()
    {
        $handler = new ApprovalHandler(auth()->user()->company_id);
        $this->histories = $handler->getApprovalHistories($this->approvalId);
    }
    
    public function render()
    {
        return view('livewire.approval-history-component');
    }
}
```

### Livewire Blade View

```blade
<!-- resources/views/livewire/approval-history-component.blade.php -->
<div class="space-y-4">
    <h3 class="text-lg font-semibold">Approval History</h3>
    
    @forelse($histories as $history)
        <div class="border rounded-lg p-4 bg-white shadow-sm">
            <div class="flex justify-between items-start mb-2">
                <div>
                    <h4 class="font-semibold text-gray-900">{{ $history['title'] }}</h4>
                    <p class="text-sm text-gray-600">
                        by {{ $history['user_name'] }} 
                        ({{ $history['user_email'] }})
                    </p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-medium
                    @if($history['flag'] === 'approved') bg-green-100 text-green-800
                    @elseif($history['flag'] === 'rejected') bg-red-100 text-red-800
                    @else bg-gray-100 text-gray-800
                    @endif">
                    {{ ucfirst($history['flag']) }}
                </span>
            </div>
            
            @if($history['flow_step_name'])
                <p class="text-sm text-gray-500 mb-2">
                    <strong>Step:</strong> {{ $history['flow_step_name'] }}
                </p>
            @endif
            
            @if($history['notes'])
                <p class="text-sm text-gray-700 mb-2">
                    <strong>Notes:</strong> {{ $history['notes'] }}
                </p>
            @endif
            
            @if($history['media_url'])
                <div class="mt-3 pt-3 border-t">
                    <a href="{{ $history['media_url'] }}" 
                       target="_blank"
                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Download Attachment
                    </a>
                </div>
            @endif
            
            <p class="text-xs text-gray-400 mt-2">
                {{ date('F j, Y \a\t g:i A', $history['date_time']) }}
            </p>
        </div>
    @empty
        <p class="text-gray-500 text-center py-8">No history records found.</p>
    @endforelse
</div>
```

## Vue.js / React Example

### Vue 3 Component

```vue
<template>
  <div class="approval-history">
    <h3 class="text-xl font-bold mb-4">Approval History</h3>
    
    <div v-for="history in histories" :key="history.id" class="history-card mb-4">
      <div class="card-header">
        <h4>{{ history.title }}</h4>
        <span :class="['badge', `badge-${history.flag}`]">
          {{ history.flag }}
        </span>
      </div>
      
      <div class="card-body">
        <p><strong>By:</strong> {{ history.user_name }}</p>
        <p><strong>Date:</strong> {{ formatDate(history.date_time) }}</p>
        <p v-if="history.notes"><strong>Notes:</strong> {{ history.notes }}</p>
        
        <a v-if="history.media_url" 
           :href="history.media_url" 
           target="_blank"
           class="btn btn-primary mt-2">
          Download Attachment
        </a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  approvalId: {
    type: Number,
    required: true
  }
});

const histories = ref([]);

const loadHistories = async () => {
  try {
    const response = await axios.get(`/api/approvals/${props.approvalId}/history`);
    histories.value = response.data.data;
  } catch (error) {
    console.error('Failed to load histories:', error);
  }
};

const formatDate = (timestamp) => {
  return new Date(timestamp * 1000).toLocaleString();
};

onMounted(() => {
  loadHistories();
});
</script>
```

### React Component

```jsx
import React, { useState, useEffect } from 'react';
import axios from 'axios';

const ApprovalHistory = ({ approvalId }) => {
  const [histories, setHistories] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadHistories();
  }, [approvalId]);

  const loadHistories = async () => {
    try {
      const response = await axios.get(`/api/approvals/${approvalId}/history`);
      setHistories(response.data.data);
    } catch (error) {
      console.error('Failed to load histories:', error);
    } finally {
      setLoading(false);
    }
  };

  const formatDate = (timestamp) => {
    return new Date(timestamp * 1000).toLocaleString();
  };

  if (loading) return <div>Loading...</div>;

  return (
    <div className="approval-history">
      <h3 className="text-xl font-bold mb-4">Approval History</h3>
      
      {histories.map((history) => (
        <div key={history.id} className="border rounded-lg p-4 mb-4 bg-white shadow">
          <div className="flex justify-between items-start mb-2">
            <div>
              <h4 className="font-semibold">{history.title}</h4>
              <p className="text-sm text-gray-600">
                by {history.user_name} ({history.user_email})
              </p>
            </div>
            <span className={`badge badge-${history.flag}`}>
              {history.flag}
            </span>
          </div>
          
          {history.notes && (
            <p className="text-sm mb-2">
              <strong>Notes:</strong> {history.notes}
            </p>
          )}
          
          {history.media_url && (
            <a
              href={history.media_url}
              target="_blank"
              rel="noopener noreferrer"
              className="inline-block mt-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
            >
              Download Attachment
            </a>
          )}
          
          <p className="text-xs text-gray-400 mt-2">
            {formatDate(history.date_time)}
          </p>
        </div>
      ))}
    </div>
  );
};

export default ApprovalHistory;
```

## DataTables Integration

```php
// Controller
public function getHistoryDataTable($approvalId)
{
    $handler = new ApprovalHandler(auth()->user()->company_id);
    $histories = $handler->getApprovalHistories($approvalId);
    
    return datatables()->of($histories)
        ->editColumn('date_time', function($history) {
            return date('Y-m-d H:i:s', $history['date_time']);
        })
        ->addColumn('attachment', function($history) {
            if ($history['media_url']) {
                return '<a href="' . $history['media_url'] . '" target="_blank" class="btn btn-sm btn-primary">
                    <i class="fa fa-download"></i> Download
                </a>';
            }
            return '-';
        })
        ->rawColumns(['attachment'])
        ->make(true);
}
```

```javascript
// JavaScript
$(document).ready(function() {
    $('#historyTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '/api/approvals/' + approvalId + '/history/datatable',
        columns: [
            { data: 'title', name: 'title' },
            { data: 'user_name', name: 'user_name' },
            { data: 'flag', name: 'flag' },
            { data: 'notes', name: 'notes' },
            { data: 'date_time', name: 'date_time' },
            { data: 'attachment', name: 'attachment', orderable: false, searchable: false }
        ]
    });
});
```

## Excel Export Example

```php
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ApprovalHistoryExport implements FromCollection, WithHeadings
{
    protected $approvalId;
    
    public function __construct($approvalId)
    {
        $this->approvalId = $approvalId;
    }
    
    public function collection()
    {
        $handler = new ApprovalHandler(auth()->user()->company_id);
        $histories = $handler->getApprovalHistories($this->approvalId);
        
        return collect($histories)->map(function($history) {
            return [
                'Title' => $history['title'],
                'User' => $history['user_name'],
                'Email' => $history['user_email'],
                'Status' => $history['flag'],
                'Notes' => $history['notes'],
                'Date' => date('Y-m-d H:i:s', $history['date_time']),
                'Attachment URL' => $history['media_url'] ?? 'No attachment',
            ];
        });
    }
    
    public function headings(): array
    {
        return ['Title', 'User', 'Email', 'Status', 'Notes', 'Date', 'Attachment URL'];
    }
}

// Usage
return Excel::download(new ApprovalHistoryExport($approvalId), 'approval-history.xlsx');
```

## PDF Export Example

```php
use Barryvdh\DomPDF\Facade\Pdf;

public function exportHistoryPdf($approvalId)
{
    $handler = new ApprovalHandler(auth()->user()->company_id);
    $histories = $handler->getApprovalHistories($approvalId);
    
    $pdf = Pdf::loadView('pdf.approval-history', [
        'histories' => $histories,
        'approvalId' => $approvalId
    ]);
    
    return $pdf->download('approval-history-' . $approvalId . '.pdf');
}
```

```blade
<!-- resources/views/pdf/approval-history.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Approval History - {{ $approvalId }}</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .history-item { margin-bottom: 20px; padding: 10px; border: 1px solid #ddd; }
        .badge { padding: 5px 10px; border-radius: 3px; }
        .badge-approved { background: #d4edda; color: #155724; }
        .badge-rejected { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <h1>Approval History</h1>
    <p>Approval ID: {{ $approvalId }}</p>
    
    @foreach($histories as $history)
        <div class="history-item">
            <h3>{{ $history['title'] }}</h3>
            <p><strong>By:</strong> {{ $history['user_name'] }} ({{ $history['user_email'] }})</p>
            <p><strong>Status:</strong> <span class="badge badge-{{ $history['flag'] }}">{{ $history['flag'] }}</span></p>
            <p><strong>Date:</strong> {{ date('Y-m-d H:i:s', $history['date_time']) }}</p>
            
            @if($history['notes'])
                <p><strong>Notes:</strong> {{ $history['notes'] }}</p>
            @endif
            
            @if($history['media_url'])
                <p><strong>Attachment:</strong> {{ $history['media_url'] }}</p>
            @endif
        </div>
    @endforeach
</body>
</html>
```
