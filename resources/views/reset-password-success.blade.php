@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center p-6 bg-slate-50">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
        <div class="p-8 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-green-50 rounded-full mb-6">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            
            <h2 class="text-2xl font-bold text-slate-900">Senha Atualizada!</h2>
            <p class="text-slate-500 mt-2 mb-8">Sua senha foi redefinida com sucesso. Você já pode voltar para o aplicativo e fazer login com sua nova senha.</p>

            <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 mb-8 inline-block">
                <p class="text-sm text-slate-600">Você já pode fechar esta aba e retornar ao app.</p>
            </div>
        </div>
    </div>
</div>
@endsection
