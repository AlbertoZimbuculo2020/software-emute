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

        // Generate random 4-digit activation code
        $codigo = str_pad(random_int(1000, 9999), 4, '0', STR_PAD_LEFT);

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

        // Build the email message matching the format shown in the image
        $mensagem = "Código de Ativação do Software EMUTE\n\n";
        $mensagem .= "EMPRESA: " . strtoupper($request->empresa) . " / NIF: " . $request->nif . "\n";
        $mensagem .= "O código de ativação é: " . $codigo . " .\n";
        $mensagem .= "Licença: " . $duracao['label'] . ".\n";
        $mensagem .= "Módulos: Todos.\n";

        // Send email to the client
        try {
            Mail::raw($mensagem, function ($message) use ($request) {
                $message->to($request->email)
                    ->subject('Código de Ativação do Software EMUTE');
            });

            return back()->with('success', 'Código de ativação enviado para ' . $request->email . '! Verifique a sua caixa de entrada.');
        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'Erro ao enviar email: ' . $e->getMessage()]);
        }
    }

    public function ativar(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string|size:4',
        ]);

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
