@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center p-6 bg-slate-50">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
        <div class="p-8">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-brand-50 rounded-full mb-4">
                    <svg class="w-8 h-8 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-slate-900">Redefinir Senha</h2>
                <p class="text-slate-500 mt-2">Escolha uma nova senha segura para sua conta.</p>
            </div>

            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-50 border border-red-100 rounded-xl">
                    <ul class="list-disc list-inside text-sm text-red-600 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('password.update.web') }}" method="POST" class="space-y-6 text-slate-900">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">E-mail</label>
                    <input type="email" value="{{ $email }}" disabled 
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-500 text-sm cursor-not-allowed">
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-slate-700 mb-2">Nova Senha</label>
                    <input type="password" name="password" id="password" required 
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-all"
                        placeholder="Mínimo 8 caracteres">
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold text-slate-700 mb-2">Confirmar Nova Senha</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required 
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-all"
                        placeholder="Digite a senha novamente">
                </div>

                <button type="submit" 
                    class="w-full py-4 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl shadow-lg shadow-brand-200 transition-all transform active:scale-[0.98]">
                    Atualizar Senha
                </button>
            </form>
        </div>
    </div>
</div>
@endsection