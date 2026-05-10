<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { 
    ClipboardList, Search, Activity, User, 
    Thermometer, Weight, HeartPulse, Wind, 
    Clock, Check, ChevronRight, Save, History, Beaker
} from 'lucide-vue-next';

const props = defineProps({
    aguardando: Array,
    historico: Array
});

const searchTerm = ref('');
const selectedPaciente = ref(null);
const notification = ref({ show: false, message: '', type: 'success' });
const confirmModal = ref({ isOpen: false, title: '', message: '', onConfirm: null });

const showNotification = (message, type = 'success') => {
    notification.value = { show: true, message, type };
    setTimeout(() => {
        notification.value.show = false;
    }, 4000);
};

const form = useForm({
    IdAgenda: '',
    IdPaciente: '',
    peso: '',
    temperatura: '',
    tensao: '',
    pulso: '',
    f_respiratoria: '',
    oximetria: '',
    obs: '',
});

const filteredAguardando = computed(() => {
    if (!searchTerm.value) return props.aguardando;
    return props.aguardando.filter(p => 
        p.PacienteNome.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
        p.Codigo.toLowerCase().includes(searchTerm.value.toLowerCase())
    );
});

const selecionarPaciente = (paciente) => {
    selectedPaciente.value = paciente;
    form.IdAgenda = paciente.Codigo;
    form.IdPaciente = paciente.IdPaciente;
    form.peso = '';
    form.temperatura = '';
    form.tensao = '';
    form.pulso = '';
    form.f_respiratoria = '';
    form.oximetria = '';
    form.obs = '';
};

const enviarTriage = () => {
    if (!selectedPaciente.value) {
        showNotification('Selecione um paciente na lista primeiro.', 'error');
        return;
    }

    confirmModal.value = {
        isOpen: true,
        title: 'Enviar para Consultório',
        message: `Deseja enviar o paciente ${selectedPaciente.value.PacienteNome} para o consultório médico com os sinais vitais registrados?`,
        onConfirm: () => {
            confirmModal.value.isOpen = false;
            form.post(route('hospitalar.triagem.store'), {
                onSuccess: () => {
                    showNotification('Triagem realizada e paciente enviado para o consultório!');
                    selectedPaciente.value = null;
                    form.reset();
                },
                onError: () => {
                    showNotification('Erro ao processar triagem.', 'error');
                }
            });
        }
    };
};

const calcularIdade = (dataNascimento) => {
    if (!dataNascimento) return 'N/D';
    const birthDate = new Date(dataNascimento);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    return age + ' anos';
};
</script>

<template>
    <Head title="Triagem Hospitalar" />

    <DashboardLayout>
        <!-- Toast Notification -->
        <Transition enter-active-class="duration-300 ease-out" enter-from-class="translate-y-4 opacity-0" enter-to-class="translate-y-0 opacity-100" leave-active-class="duration-200 ease-in" leave-to-class="translate-y-4 opacity-0">
            <div v-if="notification.show" class="fixed bottom-10 right-10 z-[1000] bg-white px-6 py-4 rounded shadow-2xl border-l-4 flex items-center gap-4 min-w-[300px]" :class="notification.type === 'success' ? 'border-emerald-500' : 'border-red-500'">
                <Check v-if="notification.type === 'success'" class="w-5 h-5 text-emerald-500" />
                <Beaker v-else class="w-5 h-5 text-red-500" />
                <span class="text-[11px] font-bold text-slate-800 uppercase">{{ notification.message }}</span>
            </div>
        </Transition>

        <div class="h-[calc(100vh-64px)] flex flex-col bg-slate-100 text-[11px] text-slate-800 overflow-hidden font-sans relative">
            
            <!-- Segmented Top Action Bar -->
            <div class="flex items-center gap-2 p-2 bg-white border-b border-slate-200 shrink-0 h-[72px] shadow-sm z-10 justify-between">
                <div class="flex flex-col mx-2 justify-center h-full">
                    <div class="text-blue-600 font-black text-[9px] uppercase mb-1.5 tracking-widest flex items-center gap-1.5"><Activity class="w-3 h-3" /> Paciente em Triagem</div>
                    <div class="flex items-center gap-4">
                        <span class="font-black text-xs uppercase tracking-tighter text-slate-800">{{ selectedPaciente?.PacienteNome || 'Nenhum paciente selecionado' }}</span>
                        <span v-if="selectedPaciente" class="bg-blue-100 text-blue-600 px-2 py-0.5 rounded text-[9px] font-black tracking-widest uppercase">IDADE: {{ calcularIdade(selectedPaciente?.DataNascimento) }}</span>
                    </div>
                </div>

                <div class="flex gap-2 pr-4 h-full items-center">
                    <button @click="enviarTriage" :disabled="form.processing || !selectedPaciente" class="bg-blue-600 text-white px-8 py-2 font-black uppercase text-[10px] tracking-widest hover:bg-blue-700 transition-all rounded shadow-sm flex items-center gap-2 disabled:opacity-50">
                        <Save class="w-4 h-4" /> Enviar para Consultório
                    </button>
                </div>
            </div>

            <!-- Dashboard Sub-Header (Blue Bar) -->
            <div class="bg-slate-800 text-slate-300 h-8 flex items-center px-4 justify-between shrink-0 shadow-sm z-10 text-[10px]">
                <div class="flex items-center gap-4">
                    <span class="font-bold flex items-center gap-2"><Clock class="w-3 h-3 text-blue-400" /> Data: <span class="text-white">{{ new Date().toLocaleDateString('pt-PT') }}</span></span>
                </div>
                <div class="flex items-center gap-4">
                    <span class="font-bold">Aguardando: <span class="text-emerald-400 font-black">{{ aguardando.length }} Pacientes</span></span>
                    <div class="w-px h-3 bg-slate-600"></div>
                    <span class="font-bold">Status: <span class="text-blue-400">Sistema Operacional</span></span>
                </div>
            </div>

            <!-- Main Layout -->
            <div class="flex-1 flex overflow-hidden p-2 gap-2 relative z-0">
                
                <!-- LEFT COLUMN: SINAIS VITAIS -->
                <div class="w-1/3 flex flex-col bg-white rounded shadow-sm border border-slate-300 overflow-hidden shrink-0">
                    <div class="bg-slate-100/50 p-2 border-b border-slate-200 flex items-center justify-between shrink-0">
                        <span class="font-black text-[10px] uppercase text-slate-500 tracking-widest">Sinais Vitais</span>
                    </div>
                    
                    <div class="p-4 overflow-y-auto custom-scrollbar flex-1">
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Peso Corporal -->
                            <div class="flex flex-col">
                                <label class="text-[9px] font-black uppercase text-slate-400 mb-1 flex items-center justify-between"><span>Peso</span> <span class="text-slate-300">kg</span></label>
                                <input v-model="form.peso" type="text" placeholder="Ex: 75.5" class="border border-slate-200 rounded px-2 py-1.5 text-[11px] uppercase focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none w-full" />
                            </div>
                            
                            <!-- Temperatura -->
                            <div class="flex flex-col">
                                <label class="text-[9px] font-black uppercase text-slate-400 mb-1 flex items-center justify-between"><span>Temperatura</span> <span class="text-slate-300">°C</span></label>
                                <input v-model="form.temperatura" type="text" placeholder="Ex: 36.5" class="border border-slate-200 rounded px-2 py-1.5 text-[11px] uppercase focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none w-full" />
                            </div>

                            <!-- Pressão Arterial -->
                            <div class="flex flex-col">
                                <label class="text-[9px] font-black uppercase text-slate-400 mb-1 flex items-center justify-between"><span>Pressão Art.</span> <span class="text-slate-300">mmHg</span></label>
                                <input v-model="form.tensao" type="text" placeholder="Ex: 12/8" class="border border-slate-200 rounded px-2 py-1.5 text-[11px] uppercase focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none w-full" />
                            </div>

                            <!-- Frequência Cardíaca -->
                            <div class="flex flex-col">
                                <label class="text-[9px] font-black uppercase text-slate-400 mb-1 flex items-center justify-between"><span>Freq. Cardíaca</span> <span class="text-slate-300">bpm</span></label>
                                <input v-model="form.pulso" type="text" placeholder="Ex: 80" class="border border-slate-200 rounded px-2 py-1.5 text-[11px] uppercase focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none w-full" />
                            </div>

                            <!-- Frequência Respiratória -->
                            <div class="flex flex-col">
                                <label class="text-[9px] font-black uppercase text-slate-400 mb-1 flex items-center justify-between"><span>Freq. Resp.</span> <span class="text-slate-300">rpm</span></label>
                                <input v-model="form.f_respiratoria" type="text" placeholder="Ex: 18" class="border border-slate-200 rounded px-2 py-1.5 text-[11px] uppercase focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none w-full" />
                            </div>

                            <!-- Saturação O2 -->
                            <div class="flex flex-col">
                                <label class="text-[9px] font-black uppercase text-slate-400 mb-1 flex items-center justify-between"><span>Sat. O2</span> <span class="text-slate-300">%</span></label>
                                <input v-model="form.oximetria" type="text" placeholder="Ex: 98" class="border border-slate-200 rounded px-2 py-1.5 text-[11px] uppercase focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none w-full" />
                            </div>
                        </div>

                        <!-- Observações -->
                        <div class="mt-4 flex flex-col">
                            <label class="text-[9px] font-black uppercase text-slate-400 mb-1">Observações Clínicas</label>
                            <textarea v-model="form.obs" rows="5" class="border border-slate-200 rounded px-2 py-1.5 text-[11px] uppercase focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none w-full resize-none"></textarea>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: PACIENTES AGUARDANDO & HISTÓRICO -->
                <div class="flex-1 flex flex-col gap-2 overflow-hidden">
                    
                    <!-- Aguardando -->
                    <div class="h-1/2 flex flex-col bg-white rounded shadow-sm border border-slate-300 overflow-hidden">
                        <div class="bg-slate-100/50 p-2 border-b border-slate-200 flex items-center justify-between shrink-0">
                            <span class="font-black text-[10px] uppercase text-slate-500 tracking-widest">Aguardando Triagem</span>
                            <input v-model="searchTerm" placeholder="Pesquisar..." class="border border-slate-200 rounded px-2 py-1 text-[9px] uppercase focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none w-48" />
                        </div>
                        <div class="flex-1 overflow-auto bg-slate-50 relative">
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-slate-200 sticky top-0 z-10 shadow-sm">
                                    <tr class="text-[9px] font-black text-slate-500 uppercase tracking-widest">
                                        <th class="p-2 border-r border-slate-300">Agenda</th>
                                        <th class="p-2 border-r border-slate-300">Paciente</th>
                                        <th class="p-2 border-r border-slate-300 text-center">Especialidade</th>
                                        <th class="p-2 text-center w-10">...</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="p in filteredAguardando" :key="p.Codigo" @click="selecionarPaciente(p)" 
                                        class="border-b border-slate-200 cursor-pointer hover:bg-blue-50 transition-colors"
                                        :class="selectedPaciente?.Codigo === p.Codigo ? 'bg-blue-100/80 border-l-4 border-l-blue-500' : 'bg-white border-l-4 border-l-transparent'">
                                        <td class="p-2 border-r border-slate-50 font-bold text-slate-700">{{ p.Codigo }}</td>
                                        <td class="p-2 border-r border-slate-50 font-bold uppercase">{{ p.PacienteNome }}</td>
                                        <td class="p-2 border-r border-slate-50 text-center">
                                            <span class="bg-slate-200 text-slate-600 px-2 py-0.5 rounded text-[8px] font-black tracking-tighter uppercase">{{ p.Consulta }}</span>
                                        </td>
                                        <td class="p-2 text-center text-blue-600"><ChevronRight class="w-4 h-4 mx-auto" /></td>
                                    </tr>
                                    <tr v-if="filteredAguardando.length === 0">
                                        <td colspan="4" class="p-8 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest">Nenhum paciente aguardando triagem</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Histórico -->
                    <div class="h-1/2 flex flex-col bg-white rounded shadow-sm border border-slate-300 overflow-hidden">
                        <div class="bg-slate-100/50 p-2 border-b border-slate-200 flex items-center justify-between shrink-0">
                            <span class="font-black text-[10px] uppercase text-slate-500 tracking-widest">Histórico de Triagem (Hoje)</span>
                        </div>
                        <div class="flex-1 overflow-auto bg-slate-50 relative">
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-slate-200 sticky top-0 z-10 shadow-sm">
                                    <tr class="text-[9px] font-black text-slate-500 uppercase tracking-widest">
                                        <th class="p-2 border-r border-slate-300 w-24">Hora</th>
                                        <th class="p-2 border-r border-slate-300">Paciente</th>
                                        <th class="p-2 border-r border-slate-300 text-center">Sinais Registados</th>
                                        <th class="p-2 text-center w-24">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="h in historico" :key="h.Id" class="border-b border-slate-200 bg-white hover:bg-slate-50 transition-colors">
                                        <td class="p-2 border-r border-slate-50">{{ new Date(h.CREATED_AT).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</td>
                                        <td class="p-2 border-r border-slate-50 font-bold uppercase text-slate-700">{{ h.PacienteNome }}</td>
                                        <td class="p-2 border-r border-slate-50 text-center">
                                            <span class="text-[9px] font-bold text-slate-500">P: {{ h.Peso }}kg | T: {{ h.Temperatura }}°C | TA: {{ h.PressaoArterial }}</span>
                                        </td>
                                        <td class="p-2 text-center">
                                            <span class="bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-widest">Concluído</span>
                                        </td>
                                    </tr>
                                    <tr v-if="historico.length === 0">
                                        <td colspan="4" class="p-8 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest">Sem registos recentes</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Confirm Modal -->
        <Transition enter-active-class="duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="duration-150 ease-in" leave-to-class="opacity-0">
            <div v-if="confirmModal.isOpen" class="fixed inset-0 z-[2000] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
                <div class="bg-white rounded shadow-2xl p-6 max-w-sm w-full border border-slate-200">
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest border-b border-slate-100 pb-2 mb-4">{{ confirmModal.title }}</h3>
                    <p class="text-[11px] font-bold text-slate-600 uppercase mb-6">{{ confirmModal.message }}</p>
                    <div class="flex gap-2 justify-end">
                        <button @click="confirmModal.isOpen = false" class="px-4 py-2 bg-slate-100 text-slate-600 rounded font-black text-[9px] uppercase tracking-widest hover:bg-slate-200 transition-all">Cancelar</button>
                        <button @click="confirmModal.onConfirm" class="px-4 py-2 bg-blue-600 text-white rounded font-black text-[9px] uppercase tracking-widest hover:bg-blue-700 transition-all">Confirmar</button>
                    </div>
                </div>
            </div>
        </Transition>

    </DashboardLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; height: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>
