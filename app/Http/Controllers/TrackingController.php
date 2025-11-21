<?php

namespace App\Http\Controllers;

use App\Services\KwaiTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TrackingController extends Controller
{
    /**
     * Endpoint para receber eventos de tracking
     * Compatível com o sistema antigo (kalitrack)
     */
    public function receiveEvent(Request $request)
    {
        // Valida autenticação
        $authHeader = $request->header('Authorization');
        if ($authHeader !== 'Bearer track123456') {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $input = $request->json()->all();

        // Valida se é Facebook ou Kwai
        $isFB = !empty($input['fbclid']);
        $isKwai = !$isFB && !empty($input['click_id']);

        if (!$isFB && !$isKwai) {
            return response()->json(['error' => 'Missing click_id or fbclid'], 400);
        }

        if ($isKwai) {
            // Valida campos obrigatórios do Kwai
            $validator = Validator::make($input, [
                'click_id' => 'required',
                'pixel_id' => 'required',
                'event_name' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => 'Missing required fields'], 400);
            }

            $clickId = $input['click_id'];
            $pixelId = $input['pixel_id'];
            $eventName = strtoupper($input['event_name']);

            $validEvents = [
                'EVENT_ADD_TO_CART',
                'EVENT_CONTENT_VIEW',
                'EVENT_COMPLETE_REGISTRATION',
                'EVENT_PURCHASE',
            ];

            if (!in_array($eventName, $validEvents, true)) {
                return response()->json(['error' => 'Invalid event_name for Kwai'], 400);
            }

            // Busca access_token do banco (tabela tracking_configs) ou SystemSetting
            $trackingService = KwaiTrackingService::getConfigFromDatabase('kwai', $pixelId);
            
            // Se não encontrou no banco, usa configuração padrão do SystemSetting
            if (!$trackingService) {
                $trackingService = new KwaiTrackingService();
            }
            
            // Mapeia evento para o formato do serviço
            $result = $trackingService->sendEvent(
                $eventName,
                $clickId,
                isset($input['value']) ? (float) $input['value'] : null,
                $input['content_id'] ?? null
            );

            if ($result['success']) {
                return response()->json($result['response'] ?? ['success' => true]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Erro ao enviar evento',
                ], 500);
            }
        } else {
            // Facebook tracking (implementar se necessário)
            return response()->json(['error' => 'Facebook tracking not implemented'], 400);
        }
    }
}

