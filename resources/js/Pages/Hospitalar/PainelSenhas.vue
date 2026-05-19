<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { 
    Volume2, 
    Clock, 
    Calendar,
    ArrowRight, 
    Monitor, 
    VolumeX,
    Sparkles
} from 'lucide-vue-next';

const ticketAtual = ref(null);
const historico = ref([]);
const clinicName = ref('EMUTE CLINIC');
const currentTime = ref('');
const currentDate = ref('');
const isMuted = ref(false);
const isCalling = ref(false);

// Cache do último ID chamado e hora da última chamada para controle de reprodução
let ultimoChamadoId = null;
let ultimaChamadaTime = null;

// Função para atualizar hora e data
const updateDateTime = () => {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString('pt-PT', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    currentDate.value = now.toLocaleDateString('pt-PT', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
};

// Sintetizar Som de Chime "Ding-Dong" usando Web Audio API (Super limpo, sem arquivos externos!)
const playChime = () => {
    if (isMuted.value) return Promise.resolve();

    return new Promise((resolve) => {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) {
                resolve();
                return;
            }

            const ctx = new AudioContext();
            
            // Nota 1 (Ding)
            const osc1 = ctx.createOscillator();
            const gain1 = ctx.createGain();
            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
            gain1.gain.setValueAtTime(0.15, ctx.currentTime);
            gain1.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.8);
            osc1.connect(gain1);
            gain1.connect(ctx.destination);
            
            // Nota 2 (Dong) - Toca ligeiramente depois e mais baixa
            const osc2 = ctx.createOscillator();
            const gain2 = ctx.createGain();
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(440.00, ctx.currentTime + 0.35); // A4
            gain2.gain.setValueAtTime(0, ctx.currentTime);
            gain2.gain.setValueAtTime(0.15, ctx.currentTime + 0.35);
            gain2.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 1.2);
            osc2.connect(gain2);
            gain2.connect(ctx.destination);

            osc1.start(ctx.currentTime);
            osc1.stop(ctx.currentTime + 0.8);
            osc2.start(ctx.currentTime + 0.35);
            osc2.stop(ctx.currentTime + 1.2);

            setTimeout(resolve, 800); // Aguarda o som acabar antes de começar a falar
        } catch (e) {
            console.error('AudioContext falhou:', e);
            resolve();
        }
    });
};

// Auxiliar para garantir que o local seja exibido e pronunciado como "Balcão"
const formatarLocal = (local) => {
    if (!local) return '';
    let res = local.replace(/guiché/gi, 'Balcão').replace(/guiche/gi, 'Balcão');
    if (!res.toLowerCase().includes('balcão') && !res.toLowerCase().includes('balcao')) {
        res = 'Balcão ' + res;
    }
    return res;
};

// Falar a Senha com voz feminina em Português usando SpeechSynthesis
const anunciarSenhaPorVoz = (codigo, guiche) => {
    if (isMuted.value || !window.speechSynthesis) return;

    // Converter código de senha em frase natural soletrada
    // Ex: "G-002" -> "Geral" + "zero zero dois"
    const partes = codigo.split('-');
    const prefixo = partes[0] || '';
    const numero = partes[1] || '';

    let tipoTraduzido = 'Senha';
    if (prefixo === 'G') tipoTraduzido = 'Senha Geral';
    else if (prefixo === 'P') tipoTraduzido = 'Senha Preferencial';
    else if (prefixo === 'T') tipoTraduzido = 'Senha de Triagem';
    else if (prefixo === 'E') tipoTraduzido = 'Senha de Exame';

    // Soletrar os números individualmente para máxima clareza
    const digitos = numero.split('').map(d => {
        if (d === '0') return 'zero';
        if (d === '1') return 'um';
        if (d === '2') return 'dois';
        if (d === '3') return 'três';
        if (d === '4') return 'quatro';
        if (d === '5') return 'cinco';
        if (d === '6') return 'seis';
        if (d === '7') return 'sete';
        if (d === '8') return 'oito';
        if (d === '9') return 'nove';
        return d;
    }).join(' ');

    const localFormatado = formatarLocal(guiche);
    const textoParaFalar = `${tipoTraduzido}, ${digitos}. Dirija-se ao, ${localFormatado}.`;

    const utterance = new SpeechSynthesisUtterance(textoParaFalar);
    utterance.lang = 'pt-PT';
    utterance.rate = 0.82; // Velocidade ligeiramente pausada e profissional
    utterance.pitch = 1.05; // Tom de voz mais suave

    // Procurar por voz feminina em Português
    const voices = window.speechSynthesis.getVoices();
    let selectedVoice = null;

    // Filtros de busca por voz de preferência feminina
    const targetVoices = voices.filter(v => v.lang.startsWith('pt'));
    
    // Procura por vozes femininas conhecidas como Microsoft Maria, Google português, Joana, Raquel, etc.
    const femaleKeywords = ['maria', 'joana', 'raquel', 'helena', 'zira', 'google', 'female', 'mulher', 'portugal'];
    
    for (const kw of femaleKeywords) {
        selectedVoice = targetVoices.find(v => v.name.toLowerCase().includes(kw));
        if (selectedVoice) break;
    }

    // Fallback para qualquer voz em Português se não achou específica feminina
    if (!selectedVoice) {
        selectedVoice = targetVoices[0];
    }

    if (selectedVoice) {
        utterance.voice = selectedVoice;
    }

    window.speechSynthesis.speak(utterance);
};

// Efetuar Polling dos dados do Painel
const fetchDadosPainel = async () => {
    try {
        const response = await axios.get(route('senhas.painel-dados'));
        const { atual, historico: hist } = response.data;
        
        ticketAtual.value = atual;
        historico.value = hist;

        // Se houver uma nova senha chamada ou chamada re-acionada
        if (atual) {
            const chamadoId = atual.Id;
            const chamadaTime = atual.DataUltimaChamada;

            if (chamadoId !== ultimoChamadoId || chamadaTime !== ultimaChamadaTime) {
                // Atualizar referências
                ultimoChamadoId = chamadoId;
                ultimaChamadaTime = chamadaTime;

                // Ativar efeito de chamada (piscar)
                isCalling.value = true;
                
                // Parar qualquer som em andamento
                if (window.speechSynthesis) {
                    window.speechSynthesis.cancel();
                }

                // Tocar o sino e falar a senha
                playChime().then(() => {
                    anunciarSenhaPorVoz(atual.Codigo, atual.Guiche);
                });

                // Desativar efeito visual de piscar após 6 segundos
                setTimeout(() => {
                    isCalling.value = false;
                }, 6000);
            }
        }
    } catch (e) {
        console.error('Erro ao buscar dados do painel de senhas:', e);
    }
};

let timerDateTime = null;
let timerPolling = null;

onMounted(() => {
    updateDateTime();
    timerDateTime = setInterval(updateDateTime, 1000);
    
    // Polling a cada 3 segundos
    fetchDadosPainel();
    timerPolling = setInterval(fetchDadosPainel, 3000);

    // Carregar vozes na API de fala para prontidão
    if (window.speechSynthesis) {
        window.speechSynthesis.getVoices();
        window.speechSynthesis.onvoiceschanged = () => {
            window.speechSynthesis.getVoices();
        };
    }
});

onUnmounted(() => {
    clearInterval(timerDateTime);
    clearInterval(timerPolling);
    if (window.speechSynthesis) {
        window.speechSynthesis.cancel();
    }
});

const toggleMute = () => {
    isMuted.value = !isMuted.value;
    if (isMuted.value && window.speechSynthesis) {
        window.speechSynthesis.cancel();
    }
};
</script>

<template>
    <div class="min-h-screen w-full bg-gradient-to-br from-[#060d16] via-[#091524] to-[#04080e] font-sans text-white p-6 md:p-8 flex flex-col justify-between overflow-hidden relative select-none">
        
        <!-- Orbes de luz decorativos premium para TV -->
        <div class="absolute top-[-20%] right-[-10%] w-[600px] h-[600px] bg-blue-500/5 rounded-full blur-[120px] pointer-events-none"></div>
        <div class="absolute bottom-[-25%] left-[-15%] w-[700px] h-[700px] bg-emerald-500/5 rounded-full blur-[140px] pointer-events-none"></div>

        <!-- Header: Logo, Nome, Data e Hora -->
        <header class="flex items-center justify-between border-b border-white/5 pb-5 relative z-10">
            <div class="flex items-center space-x-4">
                <div class="bg-blue-600/20 border border-blue-500/30 p-2.5 rounded-2xl shadow-inner backdrop-blur-md">
                    <img src="/images/logo.png" class="h-10 w-auto object-contain" alt="Logo" />
                </div>
                <div>
                    <h1 class="text-lg font-black tracking-widest text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-indigo-300">EMUTE SOFTWARE</h1>
                    <p class="text-[10px] font-bold text-gray-400 tracking-[0.2em] uppercase">Painel de Senhas Hospitalar</p>
                </div>
            </div>

            <!-- Centro: Status Online -->
            <div class="hidden lg:flex items-center space-x-2 bg-emerald-500/10 border border-emerald-500/20 px-4 py-2 rounded-full backdrop-blur-md">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span class="text-[11px] font-black text-emerald-400 tracking-wider uppercase">Painel Ativo</span>
            </div>

            <!-- Direita: Relógio e Data -->
            <div class="flex items-center space-x-6">
                <!-- Controle de Mute -->
                <button 
                    @click="toggleMute" 
                    :class="isMuted ? 'bg-red-500/10 border-red-500/20 text-red-400' : 'bg-white/5 border-white/10 text-gray-400 hover:text-white'"
                    class="p-2.5 rounded-xl border transition-all"
                    title="Ativar/Desativar Voz"
                >
                    <VolumeX v-if="isMuted" class="w-4 h-4" />
                    <Volume2 v-else class="w-4 h-4" />
                </button>

                <div class="flex flex-col text-right">
                    <span class="text-2xl font-black tracking-tight text-white font-mono">{{ currentTime }}</span>
                    <span class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider mt-0.5">{{ currentDate }}</span>
                </div>
            </div>
        </header>

        <!-- Main Body -->
        <main class="flex-grow my-8 grid grid-cols-1 lg:grid-cols-3 gap-8 relative z-10">
            
            <!-- Coluna 1 & 2: Senha Chamada Atualmente -->
            <div class="lg:col-span-2 flex flex-col justify-center relative">
                
                <transition name="scale" mode="out-in">
                    <div 
                        v-if="ticketAtual" 
                        :class="[
                            isCalling ? 'border-emerald-500/50 shadow-[0_0_80px_rgba(16,185,129,0.25)] scale-[1.02]' : 'border-white/10 shadow-[0_0_60px_rgba(59,130,246,0.1)]',
                            'relative w-full aspect-[16/9] lg:h-[480px] rounded-[3rem] bg-white/[0.02] backdrop-blur-xl border flex flex-col justify-between p-12 transition-all duration-500 overflow-hidden'
                        ]"
                    >
                        <!-- Flash effect background when calling -->
                        <div v-if="isCalling" class="absolute inset-0 bg-emerald-500/5 animate-pulse pointer-events-none"></div>

                        <!-- Top row -->
                        <div class="flex items-center justify-between">
                            <span class="flex items-center space-x-2 text-xs font-bold text-gray-400 tracking-[0.2em] uppercase">
                                <Sparkles class="w-4 h-4 text-amber-400 animate-spin" style="animation-duration: 8s" />
                                <span>Senha em Atendimento</span>
                            </span>
                            <span 
                                :class="[
                                    ticketAtual.Tipo === 'Preferencial' ? 'from-amber-400 to-orange-500 shadow-amber-500/20' : 
                                    ticketAtual.Tipo === 'Triagem' ? 'from-emerald-400 to-teal-500 shadow-emerald-500/20' : 
                                    ticketAtual.Tipo === 'Exame' ? 'from-purple-400 to-indigo-500 shadow-purple-500/20' :
                                    'from-blue-400 to-cyan-500 shadow-blue-500/20',
                                    'px-5 py-1.5 rounded-full text-[10px] font-black uppercase tracking-[0.15em] bg-gradient-to-r text-black shadow-lg'
                                ]"
                            >
                                {{ ticketAtual.Tipo }}
                            </span>
                        </div>

                        <!-- Big Center Code -->
                        <div class="text-center py-2 relative">
                            <div 
                                :class="isCalling ? 'animate-bounce' : ''"
                                class="text-[120px] sm:text-[140px] lg:text-[180px] font-black tracking-tighter leading-none text-transparent bg-clip-text bg-gradient-to-r from-white via-slate-100 to-gray-400 filter drop-shadow-[0_4px_15px_rgba(255,255,255,0.1)] transition-all font-mono"
                            >
                                {{ ticketAtual.Codigo }}
                            </div>
                        </div>

                        <!-- Bottom row: Destination badge -->
                        <div class="flex flex-col items-center justify-center space-y-4">
                            <div class="text-xs font-bold text-gray-400 tracking-[0.2em] uppercase">Por favor, dirija-se ao:</div>
                            <div class="inline-flex items-center space-x-3 bg-gradient-to-r from-blue-600 to-indigo-600 border border-blue-500/30 text-white font-black px-12 py-5 rounded-[2rem] text-2xl tracking-wide shadow-xl shadow-blue-900/30 animate-pulse">
                                <Monitor class="w-6 h-6 mr-1" />
                                <span>{{ formatarLocal(ticketAtual.Guiche) }}</span>
                            </div>
                        </div>

                    </div>

                    <!-- Estado Vazio (Nenhuma senha chamada hoje) -->
                    <div 
                        v-else 
                        class="w-full aspect-[16/9] lg:h-[480px] rounded-[3rem] bg-white/[0.02] backdrop-blur-xl border border-white/5 flex flex-col items-center justify-center p-12 text-center"
                    >
                        <div class="w-20 h-20 rounded-full bg-white/5 flex items-center justify-center mb-6">
                            <Monitor class="w-10 h-10 text-gray-400" />
                        </div>
                        <h2 class="text-2xl font-black text-white leading-tight">Painel Iniciado com Sucesso</h2>
                        <p class="text-sm text-gray-400 mt-2 max-w-md">Aguardando a primeira chamada de senhas por parte da recepção ou consultórios médicos.</p>
                    </div>
                </transition>

            </div>

            <!-- Coluna 3: Histórico de Chamadas Recentes -->
            <div class="flex flex-col justify-between">
                
                <div class="bg-white/[0.02] border border-white/5 rounded-[2.5rem] p-6 backdrop-blur-md flex flex-col h-full justify-between">
                    <div>
                        <div class="flex items-center justify-between border-b border-white/5 pb-4 mb-4">
                            <h3 class="text-xs font-black uppercase tracking-[0.2em] text-gray-400 flex items-center">
                                <Clock class="w-4 h-4 mr-2 text-blue-400" /> Chamadas Anteriores
                            </h3>
                            <span class="text-[9px] font-bold bg-white/5 border border-white/10 px-2 py-0.5 rounded text-gray-400">HISTÓRICO</span>
                        </div>

                        <!-- Lista do Histórico -->
                        <div v-if="historico.length > 0" class="space-y-3.5">
                            <div 
                                v-for="(item, idx) in historico" 
                                :key="item.Id" 
                                class="flex items-center justify-between p-4 rounded-2xl bg-white/[0.02] border border-white/[0.05] transition-all hover:bg-white/[0.04]"
                            >
                                <div class="flex items-center space-x-3.5">
                                    <div class="h-10 w-10 rounded-xl bg-white/5 flex items-center justify-center text-xs font-black text-gray-300 font-mono">
                                        {{ idx + 1 }}
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-xl font-bold tracking-tight text-white font-mono">{{ item.Codigo }}</span>
                                        <span class="text-[9px] font-bold text-gray-400 tracking-wider uppercase mt-0.5">{{ item.Tipo }}</span>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-2">
                                    <ArrowRight class="w-3.5 h-3.5 text-gray-500" />
                                    <span class="bg-blue-500/10 border border-blue-500/20 text-blue-300 px-3.5 py-1.5 rounded-xl text-[11px] font-black tracking-wide uppercase">
                                        {{ formatarLocal(item.Guiche) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Sem Histórico -->
                        <div v-else class="text-center py-16 text-gray-500">
                            <p class="text-xs font-semibold">Sem chamadas anteriores registadas.</p>
                        </div>
                    </div>

                    <!-- Rodapé Informativo -->
                    <div class="border-t border-white/5 pt-4 mt-4 flex items-center justify-between text-[10px] text-gray-500 font-semibold">
                        <span>SUGESTÃO: ATIVE O SOM NA TV</span>
                        <div class="flex items-center space-x-1.5 text-blue-400 animate-pulse">
                            <span class="w-1.5 h-1.5 bg-blue-500 rounded-full"></span>
                            <span class="uppercase tracking-wider">Apoio por Voz</span>
                        </div>
                    </div>
                </div>

            </div>

        </main>

        <!-- Footer -->
        <footer class="border-t border-white/5 pt-5 flex items-center justify-between text-[10px] text-gray-500 font-semibold relative z-10">
            <span>MUTECODE &copy; {{ new Date().getFullYear() }} - TODOS OS DIREITOS RESERVADOS</span>
            <div class="flex items-center space-x-1.5">
                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                <span class="uppercase tracking-wider text-emerald-400">Sistema Conectado via Inertia</span>
            </div>
        </footer>

    </div>
</template>

<style scoped>
.font-mono {
    font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
}

/* Transições Scale de Premium TV */
.scale-enter-active,
.scale-leave-active {
    transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.scale-enter-from {
    transform: scale(0.9);
    opacity: 0;
}
.scale-leave-to {
    transform: scale(0.95);
    opacity: 0;
}
</style>
