@php
    $app = $record;
@endphp

<div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 mb-6">
    <!-- Section Header -->
    <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-6">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-blue-50 text-blue-600 rounded-lg">
                <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-bold text-gray-800">Selection & Approval Workflow</h3>
                <p class="text-xs text-gray-400">Step-by-step selection decisions, technical approvals, and announcement release</p>
            </div>
        </div>
        
        <!-- Final Status Badge -->
        <div>
            @if($app->announcement_status === 'published')
                @if($app->status === 'accepted')
                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200 uppercase tracking-wider">Status: Hired</span>
                @else
                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 border border-red-200 uppercase tracking-wider">Status: Rejected</span>
                @endif
            @else
                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-amber-50 text-amber-700 border border-amber-200 uppercase tracking-wider">Status: In Progress</span>
            @endif
        </div>
    </div>

    <!-- 3-Step Horizontal Timeline -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 relative">
        
        <!-- STEP 1: HRD Selection Decision -->
        <div class="border rounded-xl p-4 transition-all duration-300 relative bg-white {{ match($app->hrd_decision) {
            'recommended' => 'border-emerald-200 bg-emerald-50/20 shadow-sm shadow-emerald-50',
            'rejected' => 'border-red-200 bg-red-50/20 shadow-sm shadow-red-50',
            default => 'border-gray-200 shadow-sm',
        } }}">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[10px] font-bold tracking-wider uppercase {{ match($app->hrd_decision) {
                    'recommended' => 'text-emerald-800',
                    'rejected' => 'text-red-800',
                    default => 'text-blue-800',
                } }}">Step 1: HRD Selection</span>
                
                @if($app->hrd_decision === 'recommended')
                    <span class="px-2 py-0.5 text-[9px] font-bold uppercase rounded bg-emerald-100 text-emerald-800 border border-emerald-200">Recommended</span>
                @elseif($app->hrd_decision === 'rejected')
                    <span class="px-2 py-0.5 text-[9px] font-bold uppercase rounded bg-red-100 text-red-800 border border-red-200">Rejected</span>
                @else
                    <span class="px-2 py-0.5 text-[9px] font-bold uppercase rounded bg-amber-50 text-amber-700 border border-amber-200 animate-pulse">Awaiting Review</span>
                @endif
            </div>
            
            <p class="text-xs text-gray-500 mb-3">Recruiter evaluates decision recommendation and AI summary to submit hiring recommendation.</p>
            
            @if($app->hrd_decision !== 'pending')
                <div class="bg-white border border-gray-100 rounded-lg p-2.5 mt-2 shadow-xs">
                    <span class="text-[9px] font-bold text-gray-400 uppercase block mb-1">HRD Selection Notes:</span>
                    <p class="text-xs text-gray-700 italic">"{{ $app->hrd_notes ?? 'No notes provided.' }}"</p>
                    <div class="text-[9px] text-gray-400 mt-2 flex justify-between items-center">
                        <span>Reviewed by HR Recruiter</span>
                        <span>{{ \Carbon\Carbon::parse($app->hrd_decided_at)->format('d M Y, H:i') }}</span>
                    </div>
                </div>
            @endif
        </div>

        <!-- STEP 2: Supervisor Approval -->
        @php
            $isSvLocked = $app->hrd_decision !== 'recommended';
        @endphp
        <div class="border rounded-xl p-4 transition-all duration-300 relative bg-white {{ $isSvLocked ? 'opacity-50 border-gray-100 bg-gray-50/50' : match($app->supervisor_decision) {
            'approved' => 'border-purple-200 bg-purple-50/20 shadow-sm shadow-purple-50',
            'rejected' => 'border-red-200 bg-red-50/20 shadow-sm shadow-red-50',
            default => 'border-purple-200 bg-white ring-1 ring-purple-100 shadow-sm',
        } }}">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[10px] font-bold tracking-wider uppercase {{ $isSvLocked ? 'text-gray-400' : match($app->supervisor_decision) {
                    'approved' => 'text-purple-800',
                    'rejected' => 'text-red-800',
                    default => 'text-purple-800',
                } }}">Step 2: Supervisor Approval</span>
                
                @if($isSvLocked)
                    <span class="px-2 py-0.5 text-[9px] font-bold uppercase rounded bg-gray-100 text-gray-400 border border-gray-200">Locked</span>
                @elseif($app->supervisor_decision === 'approved')
                    <span class="px-2 py-0.5 text-[9px] font-bold uppercase rounded bg-purple-100 text-purple-800 border border-purple-200">Approved</span>
                @elseif($app->supervisor_decision === 'rejected')
                    <span class="px-2 py-0.5 text-[9px] font-bold uppercase rounded bg-red-100 text-red-800 border border-red-200">Disapproved</span>
                @else
                    <span class="px-2 py-0.5 text-[9px] font-bold uppercase rounded bg-amber-50 text-amber-700 border border-amber-200 animate-pulse">Awaiting Approval</span>
                @endif
            </div>
            
            <p class="text-xs text-gray-500 mb-3">Supervisor reviews HRD's assessment and grants the final technical approval or disapproval.</p>
            
            @if($app->supervisor_decision !== 'pending' && !$isSvLocked)
                <div class="bg-white border border-gray-100 rounded-lg p-2.5 mt-2 shadow-xs">
                    <span class="text-[9px] font-bold text-gray-400 uppercase block mb-1">Supervisor Notes:</span>
                    <p class="text-xs text-gray-700 italic">"{{ $app->supervisor_notes ?? 'No notes provided.' }}"</p>
                    <div class="text-[9px] text-gray-400 mt-2 flex justify-between items-center">
                        <span>Reviewed by Tech Lead</span>
                        <span>{{ \Carbon\Carbon::parse($app->supervisor_decided_at)->format('d M Y, H:i') }}</span>
                    </div>
                </div>
            @endif
        </div>

        <!-- STEP 3: HRD Announcement Management -->
        @php
            $isAnnounceLocked = !in_array($app->supervisor_decision, ['approved', 'rejected']);
        @endphp
        <div class="border rounded-xl p-4 transition-all duration-300 relative bg-white {{ $isAnnounceLocked ? 'opacity-50 border-gray-100 bg-gray-50/50' : match($app->announcement_status) {
            'published' => 'border-blue-200 bg-blue-50/20 shadow-sm shadow-blue-50',
            default => 'border-blue-200 bg-white ring-1 ring-blue-100 shadow-sm',
        } }}">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[10px] font-bold tracking-wider uppercase {{ $isAnnounceLocked ? 'text-gray-400' : 'text-blue-800' }}">Step 3: Announcement</span>
                
                @if($isAnnounceLocked)
                    <span class="px-2 py-0.5 text-[9px] font-bold uppercase rounded bg-gray-100 text-gray-400 border border-gray-200">Locked</span>
                @elseif($app->announcement_status === 'published')
                    <span class="px-2 py-0.5 text-[9px] font-bold uppercase rounded bg-blue-100 text-blue-800 border border-blue-200">Published</span>
                @else
                    <span class="px-2 py-0.5 text-[9px] font-bold uppercase rounded bg-amber-50 text-amber-700 border border-amber-200 animate-pulse">Awaiting Release</span>
                @endif
            </div>
            
            <p class="text-xs text-gray-500 mb-3">Upon supervisor approval, HRD releases official announcements and dispatches automatic emails.</p>
            
            @if($app->announcement_status === 'published')
                <div class="bg-white border border-blue-100 rounded-lg p-2.5 mt-2 shadow-xs">
                    <span class="text-[9px] font-bold text-blue-800 uppercase block mb-1">Official Result Released:</span>
                    <div class="text-xs text-gray-700 mb-2">
                        Kandidat dinyatakan <strong>{{ $app->status === 'accepted' ? 'DITERIMA (HIRED)' : 'TIDAK LOLOS (REJECTED)' }}</strong>.
                    </div>
                    <div class="text-[9px] text-gray-400 flex items-center justify-between mt-1">
                        <span class="text-emerald-600 font-semibold flex items-center gap-0.5">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                            Email Sent
                        </span>
                        <span>{{ \Carbon\Carbon::parse($app->announcement_published_at)->format('d M Y, H:i') }}</span>
                    </div>
                </div>
            @endif
        </div>

    </div>
</div>
