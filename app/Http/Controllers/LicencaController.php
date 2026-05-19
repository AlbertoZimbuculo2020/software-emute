<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Carbon\Carbon;

class LicencaController extends Controller
{
    public function index()
    {
        return Inertia::render('Auth/Licenca');
    }

    public function solicitar(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'empresa' => 'required|string|max:255',
            'nif' => 'required|string|max:20',
            'plano' => 'required|in:mensal,semestral,anual',
        ]);

        // Generate unique random 5-digit activation code
        do {
            $codigo = str_pad(random_int(10000, 99999), 5, '0', STR_PAD_LEFT);
        } while (DB::table('licencas')->where('codigo_ativacao', $codigo)->exists());

        // Calculate license duration
        $duracaoMap = [
            'mensal' => ['meses' => 1, 'label' => '1 Mês'],
            'semestral' => ['meses' => 6, 'label' => '6 Meses'],
            'anual' => ['meses' => 12, 'label' => '12 Meses'],
        ];

        $duracao = $duracaoMap[$request->plano];

        // Save to database
        DB::table('licencas')->insert([
            'email' => $request->email,
            'empresa' => strtoupper($request->empresa),
            'nif' => $request->nif,
            'plano' => $request->plano,
            'codigo_ativacao' => $codigo,
            'ativado' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Build the email message for the company manager
        $mensagem = "📋 Nova Solicitação de Licença - Software EMUTE\n\n";
        $mensagem .= "EMPRESA: " . strtoupper($request->empresa) . "\n";
        $mensagem .= "NIF: " . $request->nif . "\n";
        $mensagem .= "EMAIL DO CLIENTE: " . $request->email . "\n";
        $mensagem .= "PLANO: " . $duracao['label'] . "\n\n";
        $mensagem .= "CÓDIGO DE ATIVAÇÃO: " . $codigo . "\n\n";
        $mensagem .= "Módulos: Todos.\n\n";
        $mensagem .= "⚠️ Envie este código ao cliente somente após a confirmação do pagamento.";

        // Send email to the company (not to the client)
        $emailEmpresa = 'mauromutete2@gmail.com';

        try {
            Mail::raw($mensagem, function ($message) use ($emailEmpresa, $request) {
                $message->to($emailEmpresa)
                    ->subject('Nova Solicitação de Licença - ' . strtoupper($request->empresa));
            });

            return back()->with('success', 'Pedido de licença enviado com sucesso! A equipa EMUTE entrará em contacto através do email ' . $request->email . ' após a confirmação do pagamento.');
        } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
            $msg = $e->getMessage();

            if (str_contains($msg, 'Username and Password not accepted') || str_contains($msg, 'BadCredentials')) {
                $errorMsg = 'As credenciais do servidor de email estão incorretas. Contacte o suporte técnico para configurar o envio de emails.';
            } elseif (str_contains($msg, 'Connection could not be established') || str_contains($msg, 'Connection refused')) {
                $errorMsg = 'Não foi possível conectar ao servidor de email. Verifique a sua ligação à internet e tente novamente.';
            } elseif (str_contains($msg, 'Timed out')) {
                $errorMsg = 'O servidor de email demorou a responder. Tente novamente em alguns instantes.';
            } else {
                $errorMsg = 'Não foi possível enviar o email neste momento. Tente novamente mais tarde ou contacte o suporte.';
            }

            return back()->withErrors(['email' => $errorMsg]);
        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'Ocorreu um erro inesperado ao processar o seu pedido. Tente novamente mais tarde.']);
        }
    }

    public function ativar(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string|size:5',
        ]);

        // Check if the activation code exists in the database
        $codigoExiste = DB::table('licencas')
            ->where('codigo_ativacao', $request->codigo)
            ->exists();

        if (!$codigoExiste) {
            return back()->withErrors(['codigo' => 'O código de ativação introduzido é inválido.']);
        }

        // Check if the code has already been used (activated)
        $licencaJaAtivada = DB::table('licencas')
            ->where('codigo_ativacao', $request->codigo)
            ->where('ativado', true)
            ->exists();

        if ($licencaJaAtivada) {
            return back()->withErrors(['codigo' => 'Este código de ativação já foi utilizado e não pode ser usado outra vez.']);
        }

        // Find the license by code (most recent, not yet activated)
        $licenca = DB::table('licencas')
            ->where('codigo_ativacao', $request->codigo)
            ->where('ativado', false)
            ->orderBy('id', 'desc')
            ->first();

        if (!$licenca) {
            return back()->withErrors(['codigo' => 'Código de ativação inválido ou já utilizado.']);
        }

        $duracaoMeses = match ($licenca->plano) {
            'mensal' => 1,
            'semestral' => 6,
            'anual' => 12,
            default => 1,
        };

        $dataInicio = Carbon::now();
        $dataFim = Carbon::now()->addMonths($duracaoMeses);

        // Activate the license
        DB::table('licencas')
            ->where('id', $licenca->id)
            ->update([
                'ativado' => true,
                'data_inicio' => $dataInicio->format('Y-m-d'),
                'data_fim' => $dataFim->format('Y-m-d'),
                'updated_at' => now(),
            ]);

        $planoLabel = match ($licenca->plano) {
            'mensal' => 'Mensal (1 Mês)',
            'semestral' => 'Semestral (6 Meses)',
            'anual' => 'Anual (12 Meses)',
            default => $licenca->plano,
        };

        return redirect()->route('login')->with('success',
            'Licença ativada com sucesso! Plano: ' . $planoLabel .
            '. Válida até ' . $dataFim->format('d/m/Y') . '.'
        );
    }
}
