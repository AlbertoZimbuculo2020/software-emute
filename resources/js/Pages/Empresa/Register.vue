<script setup>
import { ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Save, ArrowLeft, Search, User, Lock } from 'lucide-vue-next';

const props = defineProps({
    empresa: Object
});

const form = useForm({
    DESCRICAO: props.empresa?.DESCRICAO || '',
    NIF: props.empresa?.NIF || '',
    SIGLA: props.empresa?.SIGLA || '',
    NUMEROCOMERCIAL: props.empresa?.NUMEROCOMERCIAL || '',
    REGIME: props.empresa?.REGIME || '',
    INDICATIVO: props.empresa?.INDICATIVO || '',
    TELEFONE: props.empresa?.TELEFONE || '',
    TELEFONE2: props.empresa?.TELEFONE2 || '',
    EMAIL: props.empresa?.EMAIL || '',
    PROVINCIA: props.empresa?.PROVINCIA || '',
    CIDADE: props.empresa?.CIDADE || '',
    RUA: props.empresa?.RUA || '',
    IMAGEM: null,
    LOGIN: '',
    SENHA: ''
});

const defaultLogo = props.empresa?.IMAGEM || '/images/logo_official.png';
const previewUrl = ref(defaultLogo);
const logoInput = ref(null);

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.IMAGEM = file;
        previewUrl.value = URL.createObjectURL(file);
    }
};

const triggerFileInput = () => {
    if (logoInput.value) {
        logoInput.value.click();
    }
};

const submit = () => {
    form.post('/empresa/register', {
        preserveScroll: true,
        forceFormData: true,
        onError: (errors) => {
            console.error('Validation Errors:', errors);
            const firstError = Object.values(errors)[0];
            if (firstError) {
                alert('Erro de Validação: ' + firstError);
            } else {
                alert('Ocorreu um erro ao gravar. Verifique a consola.');
            }
        },
        onSuccess: () => alert('Configurações e Logótipo gravados com sucesso!'),
    });
};
</script>

<template>
    <Head title="Configuração da Empresa" />

    <div class="min-h-screen bg-[#F3F4F6] flex items-center justify-center p-4 sm:p-8 font-sans text-gray-800 relative z-0">
        
        <!-- Decorative Background Mesh -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none -z-10">
            <div class="absolute -top-40 -right-40 w-96 h-96 bg-blue-400 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
            <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-[#006BB3] rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"></div>
        </div>

        <!-- Main Card Container -->
        <div class="w-full max-w-6xl bg-white rounded-3xl shadow-2xl shadow-blue-900/10 overflow-hidden flex flex-col lg:flex-row border border-white">
            
            <!-- Left Side: Brand & Logo Upload -->
            <div class="lg:w-1/3 bg-gradient-to-br from-[#006BB3] to-[#004d80] p-10 text-white flex flex-col justify-between relative overflow-hidden shrink-0">
                <!-- Decorative background elements -->
                <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3"></div>
                <div class="absolute bottom-0 left-0 w-64 h-64 bg-[#00A3FF]/20 rounded-full blur-3xl translate-y-1/3 -translate-x-1/3"></div>

                <div class="relative z-10">
                    <button @click="router.visit(route('login'))" class="flex items-center text-sm font-medium text-white/70 hover:text-white transition-colors mb-10 group">
                        <ArrowLeft class="w-5 h-5 mr-2 group-hover:-translate-x-1 transition-transform" />
                        Voltar ao Login
                    </button>
                    
                    <h1 class="text-3xl font-extrabold tracking-tight mb-3">Configuração da Empresa</h1>
                    <p class="text-white/70 text-sm leading-relaxed mb-12">
                        Configure os detalhes da sua empresa para iniciar a operar o sistema EMUTE com a sua identidade visual e os seus dados fiscais.
                    </p>

                    <!-- Logo Upload Area -->
                    <div @click="triggerFileInput" class="w-full aspect-square bg-[#000000]/20 border border-white/20 rounded-2xl flex flex-col items-center justify-center p-8 hover:bg-[#000000]/30 transition-all group cursor-pointer relative overflow-hidden backdrop-blur-sm">
                        
                        <input type="file" ref="logoInput" @change="handleFileChange" accept="image/*" class="hidden" />

                        <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 bg-[#006BB3]/80 transition-opacity duration-300 z-20 backdrop-blur-sm">
                            <div class="flex items-center space-x-2 bg-white text-[#006BB3] px-5 py-2.5 rounded-full shadow-xl transform scale-95 group-hover:scale-100 transition-transform duration-300">
                                <Search class="w-4 h-4" />
                                <span class="text-sm font-bold">Alterar Logotipo</span>
                            </div>
                        </div>

                        <img :src="previewUrl" class="max-w-full max-h-full object-contain relative z-10 drop-shadow-2xl" alt="Logotipo da Empresa" />
                    </div>
                </div>

                <div class="relative z-10 mt-12">
                    <p class="text-xs text-white/50 text-center font-medium tracking-wider">EMUTE SOFTWARE © 2026</p>
                </div>
            </div>

            <!-- Right Side: Form Data -->
            <form @submit.prevent="submit" class="lg:w-2/3 p-8 sm:p-12 bg-white flex flex-col">
                <div class="flex justify-between items-start mb-10">
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight text-gray-900">Detalhes da Organização</h2>
                        <p class="text-sm text-gray-500 mt-1.5 font-medium">Preencha cuidadosamente os campos abaixo.</p>
                    </div>
                    <button type="submit" class="hidden sm:flex items-center justify-center bg-[#006BB3] hover:bg-[#005a96] text-white px-7 py-3.5 rounded-xl text-sm font-bold shadow-lg shadow-blue-500/30 transition-all hover:-translate-y-0.5 focus:ring-4 focus:ring-blue-500/20 active:translate-y-0">
                        <Save class="w-4 h-4 mr-2.5" />
                        Salvar Configurações
                    </button>
                </div>

                <div class="flex-grow grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                    
                    <!-- Form Group: Identificação -->
                    <div class="col-span-1 md:col-span-2 space-y-5">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Nome da Empresa / Descrição</label>
                                <input v-model="form.DESCRICAO" type="text" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block p-3.5 transition-colors uppercase font-medium placeholder-gray-400" placeholder="Ex: MUTE CODE LDA">
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">NIF</label>
                                <input v-model="form.NIF" type="text" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block p-3.5 transition-colors font-medium placeholder-gray-400" placeholder="Ex: 5000000000" maxlength="14">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Sigla</label>
                                <input v-model="form.SIGLA" type="text" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block p-3.5 transition-colors uppercase font-medium placeholder-gray-400" placeholder="Ex: MTC">
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Regime Fiscal</label>
                                <div class="relative">
                                    <select v-model="form.REGIME" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block p-3.5 transition-colors appearance-none font-medium cursor-pointer">
                                        <option value="">Selecione o Regime...</option>
                                        <option value="GERAL">Regime Geral</option>
                                        <option value="SIMPLIFICADO">Regime Simplificado</option>
                                        <option value="EXCLUSAO">Regime Exclusão</option>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center pl-2 pr-4 pointer-events-none text-gray-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Indicativo de Fatura</label>
                                <input v-model="form.INDICATIVO" type="text" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block p-3.5 transition-colors uppercase font-medium placeholder-gray-400" placeholder="Ex: FT">
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Número de Registo Comercial</label>
                            <input v-model="form.NUMEROCOMERCIAL" type="text" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block p-3.5 transition-colors font-medium placeholder-gray-400" placeholder="Ex: 12345/2026">
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="col-span-1 md:col-span-2 my-2 border-t border-gray-100"></div>

                    <!-- Contactos Section -->
                    <div class="col-span-1 space-y-6">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                            </div>
                            <h3 class="text-base font-bold text-gray-900">Contactos</h3>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Telefone Principal</label>
                                <input v-model="form.TELEFONE" type="text" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block p-3.5 shadow-sm transition-colors font-medium placeholder-gray-400" placeholder="+244 9XX 000 000">
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Telefone Alternativo</label>
                                <input v-model="form.TELEFONE2" type="text" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block p-3.5 shadow-sm transition-colors font-medium placeholder-gray-400" placeholder="+244 9XX 000 000">
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Email Geral</label>
                                <input v-model="form.EMAIL" type="email" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block p-3.5 shadow-sm transition-colors font-medium placeholder-gray-400" placeholder="geral@empresa.com">
                            </div>
                        </div>
                    </div>

                    <!-- Endereço Section -->
                    <div class="col-span-1 space-y-6">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            </div>
                            <h3 class="text-base font-bold text-gray-900">Endereço</h3>
                        </div>

                        <div class="space-y-4">
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Província</label>
                                <input v-model="form.PROVINCIA" type="text" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block p-3.5 shadow-sm transition-colors uppercase font-medium placeholder-gray-400" placeholder="Ex: Luanda">
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Cidade / Município</label>
                                <input v-model="form.CIDADE" type="text" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block p-3.5 shadow-sm transition-colors uppercase font-medium placeholder-gray-400" placeholder="Ex: Talatona">
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Rua / Bairro</label>
                                <input v-model="form.RUA" type="text" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block p-3.5 shadow-sm transition-colors uppercase font-medium placeholder-gray-400" placeholder="Morada detalhada">
                            </div>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="col-span-1 md:col-span-2 my-2 border-t border-gray-100"></div>

                    <!-- Utilizador de Acesso Section -->
                    <div class="col-span-1 md:col-span-2 space-y-6">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center">
                                <User class="w-4 h-4 text-purple-600" />
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900">Utilizador Administrador</h3>
                                <p class="text-xs text-gray-500">Credenciais para acesso inicial ao sistema (opcional caso já existam utilizadores)</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Login</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <User class="h-4 w-4 text-gray-400" />
                                    </div>
                                    <input v-model="form.LOGIN" type="text" class="w-full pl-10 bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block p-3.5 shadow-sm transition-colors font-medium placeholder-gray-400" placeholder="Utilizador Root">
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Senha</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <Lock class="h-4 w-4 text-gray-400" />
                                    </div>
                                    <input v-model="form.SENHA" type="password" class="w-full pl-10 bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block p-3.5 shadow-sm transition-colors font-medium placeholder-gray-400" placeholder="••••••••">
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Mobile Submit Button -->
                <button type="submit" class="sm:hidden w-full mt-10 flex justify-center items-center bg-[#006BB3] hover:bg-[#005a96] text-white px-6 py-4 rounded-xl text-sm font-bold shadow-lg shadow-blue-500/30 active:scale-[0.98] transition-transform">
                    <Save class="w-5 h-5 mr-2" />
                    Salvar Configurações
                </button>
            </form>
        </div>
    </div>
</template>

<style scoped>
@keyframes blob {
  0% { transform: translate(0px, 0px) scale(1); }
  33% { transform: translate(30px, -50px) scale(1.1); }
  66% { transform: translate(-20px, 20px) scale(0.9); }
  100% { transform: translate(0px, 0px) scale(1); }
}
.animate-blob {
  animation: blob 7s infinite;
}
.animation-delay-2000 {
  animation-delay: 2s;
}

/* Custom Scrollbar for potential overflow */
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
::-webkit-scrollbar-track {
    background: transparent;
}
::-webkit-scrollbar-thumb {
    background: #CBD5E1;
    border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
    background: #94A3B8;
}
</style>
