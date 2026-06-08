@extends('adminmodule::layouts.master')

@section('title', 'QR Invitation Tokens')

@section('content')
<div class="main-content">
    <div class="container-fluid">

        {{-- Page Header --}}
        <div class="d-flex flex-wrap justify-content-between align-items-center mt-30 mb-4 gap-3">
            <div>
                <h2 class="fs-22 fw-bold text-capitalize mb-1">QR Invitation Tokens</h2>
                <p class="text-muted mb-0 fs-12">Generate single-use QR codes to invite customers and drivers into the platform.</p>
            </div>
        </div>

        {{-- Generate New Token --}}
        <div class="card mb-4">
            <div class="card-header border-bottom py-3">
                <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-plus-circle text-primary"></i>
                    Generate New Invitation Token
                </h5>
            </div>
            <div class="card-body pt-4">
                <p class="text-muted small mb-3">Select the role for this invitation. Customer tokens expire in <strong>1 hour</strong>; driver tokens expire in <strong>7 days</strong>.</p>

                <form action="{{ route('admin.qr-tokens.generate') }}" method="POST">
                    @csrf
                    <div class="row g-3 align-items-end">

                        {{-- Customer card --}}
                        <div class="col-auto">
                            <input type="radio" class="btn-check" name="role" id="role-customer" value="customer" required checked>
                            <label class="btn btn-outline-primary d-flex flex-column align-items-center px-4 py-3 gap-2" for="role-customer" style="min-width:130px;">
                                <i class="bi bi-person-circle fs-2"></i>
                                <span class="fw-semibold">Customer</span>
                                <small class="text-muted fw-normal">Expires in 1 hour</small>
                            </label>
                        </div>

                        {{-- Driver card --}}
                        <div class="col-auto">
                            <input type="radio" class="btn-check" name="role" id="role-driver" value="driver">
                            <label class="btn btn-outline-primary d-flex flex-column align-items-center px-4 py-3 gap-2" for="role-driver" style="min-width:130px;">
                                <i class="bi bi-truck fs-2"></i>
                                <span class="fw-semibold">Driver</span>
                                <small class="text-muted fw-normal">Expires in 7 days</small>
                            </label>
                        </div>

                        <div class="col-auto ms-3">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-qr-code me-2"></i>Generate Token
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Token List --}}
        <div class="card">
            <div class="card-header border-bottom py-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-list-ul text-primary"></i>
                    All Tokens
                </h5>
                <form action="{{ url()->current() }}" class="search-form search-form_style-two">
                    <div class="input-group search-form__input_group">
                        <span class="search-form__icon"><i class="bi bi-search"></i></span>
                        <input type="search" name="search" value="{{ $search ?? '' }}"
                               class="theme-input-style search-form__input"
                               placeholder="Search by token or role…">
                    </div>
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-borderless align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">#</th>
                                <th>Role</th>
                                <th>Token</th>
                                <th>Created By</th>
                                <th>Expires At</th>
                                <th>Status</th>
                                <th class="text-center pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tokens as $key => $token)
                            <tr class="border-top">
                                <td class="ps-4 text-muted">{{ $tokens->firstItem() + $key }}</td>

                                {{-- Role with icon --}}
                                <td>
                                    @if($token->role === 'driver')
                                        <span class="d-flex align-items-center gap-2">
                                            <span class="bg-warning bg-opacity-10 text-warning rounded p-1 lh-1"><i class="bi bi-truck"></i></span>
                                            <span class="fw-semibold text-capitalize">Driver</span>
                                        </span>
                                    @else
                                        <span class="d-flex align-items-center gap-2">
                                            <span class="bg-primary bg-opacity-10 text-primary rounded p-1 lh-1"><i class="bi bi-person-circle"></i></span>
                                            <span class="fw-semibold text-capitalize">Customer</span>
                                        </span>
                                    @endif
                                </td>

                                {{-- Token with copy --}}
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <code class="small text-muted" style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $token->token }}">
                                            {{ $token->token }}
                                        </code>
                                        <button class="btn btn-sm btn-outline-secondary copy-btn p-1 lh-1"
                                                data-token="{{ $token->token }}"
                                                title="Copy full token">
                                            <i class="bi bi-clipboard fs-12"></i>
                                        </button>
                                    </div>
                                </td>

                                <td>{{ $token->creator?->first_name ?? '—' }}</td>
                                <td class="text-nowrap">{{ $token->expires_at?->format('M d, Y · H:i') }}</td>

                                {{-- Status badge --}}
                                <td>
                                    @if($token->is_revoked)
                                        <span class="badge bg-danger-light text-danger">Revoked</span>
                                    @elseif($token->redeemed_at)
                                        <span class="badge bg-secondary">Redeemed</span>
                                    @elseif($token->expires_at && $token->expires_at->isPast())
                                        <span class="badge bg-warning text-dark">Expired</span>
                                    @else
                                        <span class="badge bg-success">Active</span>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td class="text-center pe-4">
                                    <div class="d-flex align-items-center justify-content-center gap-2 flex-nowrap">
                                        {{-- Download QR (always available) --}}
                                        <a href="{{ route('admin.qr-tokens.download', $token->id) }}"
                                           class="btn btn-sm btn-outline-secondary"
                                           title="Download QR Code">
                                            <i class="bi bi-qr-code-scan me-1"></i>QR
                                        </a>

                                        {{-- Revoke (active only) --}}
                                        @if(!$token->is_revoked && !$token->redeemed_at && $token->expires_at?->isFuture())
                                            <form action="{{ route('admin.qr-tokens.revoke', $token->id) }}" method="POST"
                                                  onsubmit="return confirm('Revoke this token? This cannot be undone.')">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Revoke</button>
                                            </form>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="bi bi-qr-code fs-2 d-block mb-2 opacity-25"></i>
                                    No tokens found. Generate one above.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($tokens->hasPages())
                <div class="px-4 py-3 border-top">
                    {{ $tokens->links() }}
                </div>
                @endif
            </div>
        </div>

    </div>
</div>

{{-- ── New Token Modal ──────────────────────────────────────── --}}
@if(session('new_token_id'))
@php $newToken = \Modules\AuthManagement\Entities\QrToken::find(session('new_token_id')); @endphp
@if($newToken)
<div class="modal fade" id="newTokenModal" tabindex="-1" aria-labelledby="newTokenModalLabel" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="newTokenModalLabel">
                    <i class="bi bi-check-circle-fill text-success me-2"></i>Token Generated
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body text-center px-4 pt-3 pb-4">

                {{-- Role badge --}}
                <p class="text-muted small mb-3">
                    {{ ucfirst($newToken->role) }} invitation ·
                    Expires <strong>{{ $newToken->expires_at?->format('M d, Y H:i') }}</strong>
                </p>

                {{-- QR Code --}}
                <div class="d-inline-block border rounded-3 p-3 mb-3 bg-white">
                    {!! \QrCode::size(200)->margin(1)->generate($newToken->token) !!}
                </div>

                {{-- Full token --}}
                <div class="bg-light rounded-3 p-3 mb-3 text-start">
                    <label class="form-label text-muted small mb-1 fw-semibold">INVITATION TOKEN</label>
                    <div class="d-flex align-items-center gap-2">
                        <code id="modal-token-text" class="flex-grow-1 text-break small">{{ $newToken->token }}</code>
                    </div>
                </div>

                {{-- Action buttons --}}
                <div class="d-flex justify-content-center gap-2 flex-wrap">
                    <button class="btn btn-primary copy-btn" data-token="{{ $newToken->token }}" id="modalCopyBtn">
                        <i class="bi bi-clipboard me-1"></i>Copy Token
                    </button>
                    <a href="{{ route('admin.qr-tokens.download', $newToken->id) }}" class="btn btn-outline-success">
                        <i class="bi bi-download me-1"></i>Download QR
                    </a>
                </div>

            </div>
        </div>
    </div>
</div>
@endif
@endif

@push('script')
<script>
    // Auto-open modal if a new token was just generated
    const modalEl = document.getElementById('newTokenModal');
    if (modalEl) {
        new bootstrap.Modal(modalEl).show();
    }

    // Copy-to-clipboard for all .copy-btn buttons
    document.querySelectorAll('.copy-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const token = btn.getAttribute('data-token');
            navigator.clipboard.writeText(token).then(function () {
                const original = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Copied!';
                btn.classList.add('btn-success');
                btn.classList.remove('btn-primary', 'btn-outline-secondary');
                setTimeout(function () {
                    btn.innerHTML = original;
                    btn.classList.remove('btn-success');
                    btn.classList.add(btn.id === 'modalCopyBtn' ? 'btn-primary' : 'btn-outline-secondary');
                }, 2000);
            }).catch(function () {
                btn.textContent = 'Failed — copy manually';
            });
        });
    });
</script>
@endpush

@endsection
