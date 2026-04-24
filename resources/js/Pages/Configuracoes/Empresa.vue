<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { 
    Building2, Save, Upload, MapPin, Phone, 
    Mail, ShieldCheck, FileText, Fingerprint,
    Search, Camera, X, CheckCircle
} from 'lucide-vue-next';

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
    IMAGEM: null
});

const previewUrl = ref(props.empresa?.IMAGEM || null);
const logoInput = ref(null);

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.IMAGEM = file;
        previewUrl.value = URL.createObjectURL(file);
    }
};

const triggerFileInput = () => {
    logoInput.value.click();
};

const submit = () => {
    form.post(route('configuracoes.empresa.update'), {
        preserveScroll: true,
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Dados da Empresa" />

    <DashboardLayout>
        <div class="min-h-screen bg-[#F4F7FA] p-4 lg:p-8">
            <div class="max-w-6xl mx-auto">
                <!-- Header Bento -->
                <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-slate-200/60 mb-8 flex flex-col md:flex-row justify-between items-center gap-6">
                    <div class="flex items-center gap-6">
                        <div class="w-20 h-20 bg-blue-600 rounded-[2rem] flex items-center justify-center text-white shadow-xl shadow-blue-200">
                            <Building2 class="w-10 h-10" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-black text-slate-900 uppercase tracking-tight">Configurações da Empresa</h1>
                            <p class="text-sm font-bold text-slate-400 mt-1 uppercase tracking-widest">Gerencie a identidade e dados fiscais da sua organização</p>
                        </div>
                    </div>
                    <button @click="submit" :disabled="form.processing" class="bg-slate-900 text-white px-10 py-4 rounded-2xl text-xs font-black uppercase tracking-[0.2em] hover:bg-slate-800 transition-all shadow-xl shadow-slate-200 flex items-center gap-3 disabled:opacity-50">
                        <Save class="w-5 h-5" /> Salvar Alterações
                    </button>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    <!-- Left: Identity & Logo -->
                    <div class="lg:col-span-4 space-y-8">
                        <div class="bg-white rounded-[3rem] p-8 shadow-sm border border-slate-200/60 flex flex-col items-center text-center">
                            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mb-8">Logotipo Oficial</h3>
                            
                            <div @click="triggerFileInput" class="relative group cursor-pointer">
                                <div class="w-48 h-48 bg-slate-50 rounded-[3rem] border-2 border-dashed border-slate-200 flex items-center justify-center overflow-hidden transition-all group-hover:border-blue-400 group-hover:bg-blue-50">
                                    <img v-if="previewUrl" :src="previewUrl" class="w-full h-full object-contain p-4" />
                                    <div v-else class="flex flex-col items-center text-slate-300">
                                        <Camera class="w-12 h-12 mb-2" />
                                        <span class="text-[10px] font-black uppercase">Upload</span>
                                    </div>
                                    <div class="absolute inset-0 bg-blue-600/80 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white">
                                        <Upload class="w-8 h-8" />
                                    </div>
                                </div>
                                <input type="file" ref="logoInput" @change="handleFileChange" class="hidden" accept="image/*" />
                            </div>
                            <p class="text-[10px] font-bold text-slate-400 mt-6 px-8 leading-relaxed uppercase tracking-tighter">Use uma imagem quadrada com fundo transparente para melhores resultados.</p>
                        </div>

                        <div class="bg-slate-900 rounded-[3rem] p-8 text-white shadow-xl shadow-slate-200 overflow-hidden relative">
                            <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/5 rounded-full blur-3xl"></div>
                            <h3 class="text-[10px] font-black text-white/40 uppercase tracking-[0.3em] mb-6">Status Fiscal</h3>
                            <div class="space-y-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center text-emerald-400">
                                        <CheckCircle class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-black uppercase text-white/40">Sistema Operacional</p>
                                        <p class="text-sm font-bold tracking-tight">Ambiente de Produção</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center text-blue-400">
                                        <ShieldCheck class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-black uppercase text-white/40">Certificação AGT</p>
                                        <p class="text-sm font-bold tracking-tight">Validado com Sucesso</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Forms -->
                    <div class="lg:col-span-8 space-y-8">
                        <!-- Identificação -->
                        <div class="bg-white rounded-[3rem] p-10 shadow-sm border border-slate-200/60">
                            <div class="flex items-center gap-4 mb-10">
                                <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
                                    <Fingerprint class="w-5 h-5" />
                                </div>
                                <h2 class="text-lg font-black text-slate-800 uppercase tracking-tight">Identificação & Fiscalidade</h2>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Descrição / Nome Oficial</label>
                                    <input v-model="form.DESCRICAO" type="text" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 rounded-2xl p-4 text-sm font-bold transition-all uppercase" placeholder="Nome da Empresa">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">NIF (Número de Identificação Fiscal)</label>
                                    <input v-model="form.NIF" type="text" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 rounded-2xl p-4 text-sm font-bold transition-all" placeholder="5000000000">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Regime Fiscal</label>
                                    <select v-model="form.REGIME" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 rounded-2xl p-4 text-sm font-bold transition-all cursor-pointer">
                                        <option value="GERAL">Regime Geral</option>
                                        <option value="SIMPLIFICADO">Regime Simplificado</option>
                                        <option value="EXCLUSAO">Regime Exclusão</option>
                                    </select>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Sigla</label>
                                        <input v-model="form.SIGLA" type="text" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 rounded-2xl p-4 text-sm font-bold transition-all uppercase text-center" placeholder="EX: EM">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Ind. Fatura</label>
                                        <input v-model="form.INDICATIVO" type="text" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 rounded-2xl p-4 text-sm font-bold transition-all uppercase text-center" placeholder="EX: FT">
                                    </div>
                                </div>
                                <div class="md:col-span-2 space-y-2">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Registo Comercial</label>
                                    <input v-model="form.NUMEROCOMERCIAL" type="text" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 rounded-2xl p-4 text-sm font-bold transition-all" placeholder="Conservatória do Registo Comercial...">
                                </div>
                            </div>
                        </div>

                        <!-- Contactos e Localização -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="bg-white rounded-[3rem] p-10 shadow-sm border border-slate-200/60">
                                <div class="flex items-center gap-4 mb-8">
                                    <div class="w-10 h-10 rounded-full bg-amber-50 flex items-center justify-center text-amber-600">
                                        <Phone class="w-5 h-5" />
                                    </div>
                                    <h2 class="text-lg font-black text-slate-800 uppercase tracking-tight">Contactos</h2>
                                </div>
                                <div class="space-y-6">
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Telefone Principal</label>
                                        <input v-model="form.TELEFONE" type="text" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 rounded-2xl p-4 text-sm font-bold transition-all">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Email Geral</label>
                                        <input v-model="form.EMAIL" type="email" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 rounded-2xl p-4 text-sm font-bold transition-all">
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded-[3rem] p-10 shadow-sm border border-slate-200/60">
                                <div class="flex items-center gap-4 mb-8">
                                    <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600">
                                        <MapPin class="w-5 h-5" />
                                    </div>
                                    <h2 class="text-lg font-black text-slate-800 uppercase tracking-tight">Localização</h2>
                                </div>
                                <div class="space-y-6">
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Província / Cidade</label>
                                        <div class="grid grid-cols-2 gap-4">
                                            <input v-model="form.PROVINCIA" type="text" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 rounded-2xl p-4 text-sm font-bold transition-all uppercase" placeholder="Província">
                                            <input v-model="form.CIDADE" type="text" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 rounded-2xl p-4 text-sm font-bold transition-all uppercase" placeholder="Cidade">
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Endereço (Rua/Bairro)</label>
                                        <input v-model="form.RUA" type="text" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 rounded-2xl p-4 text-sm font-bold transition-all uppercase">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
input:focus, select:focus {
    outline: none;
}
</style>
