<?php

namespace Modules\AuthManagement\Http\Controllers\Web;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\AuthManagement\Entities\QrToken;

class VitoQrAdminController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->search;
        $tokens = QrToken::with('creator')
            ->when($search, function ($q) use ($search) {
                $q->where('token', 'like', "%{$search}%")
                  ->orWhere('role', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('authmanagement::admin.qr-tokens.index', compact('tokens', 'search'));
    }

    public function generate(Request $request): RedirectResponse
    {
        $request->validate([
            'role' => 'required|in:driver,customer',
        ]);

        $token = QrToken::create([
            'token'      => Str::random(64),
            'role'       => $request->role,
            'created_by' => auth()->id(),
            'expires_at' => $request->role === 'customer' ? now()->addHour() : now()->addDays(7),
        ]);

        Toastr::success('Token generated successfully.');

        return redirect()->route('admin.qr-tokens.index')->with('new_token_id', $token->id);
    }

    public function revoke(string $id): RedirectResponse
    {
        $token = QrToken::findOrFail($id);
        $token->update(['is_revoked' => true]);

        Toastr::success('Token revoked successfully.');

        return redirect()->route('admin.qr-tokens.index');
    }

    public function download(string $id): Response
    {
        $token = QrToken::findOrFail($id);

        // simple-qrcode's PNG backend requires the Imagick PHP extension. On the
        // many servers that only ship GD, format('png') throws — so fall back to
        // SVG (no extra extension needed) when Imagick is unavailable. The QR
        // scans identically either way.
        if (extension_loaded('imagick')) {
            $image = \QrCode::format('png')->size(400)->margin(2)->generate($token->token);
            $mime  = 'image/png';
            $ext   = 'png';
        } else {
            $image = \QrCode::format('svg')->size(400)->margin(2)->generate($token->token);
            $mime  = 'image/svg+xml';
            $ext   = 'svg';
        }

        return response($image)
            ->header('Content-Type', $mime)
            ->header('Content-Disposition', 'attachment; filename="vito-qr-' . $token->id . '.' . $ext . '"');
    }
}
