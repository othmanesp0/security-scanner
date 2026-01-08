<?php
/**
 * AI Analysis Helper - Python Bridge Version
 * Utilise un script Python local pour contourner les blocages réseau d'Apache.
 */

function analyzeWithGemini($scan_output, $tool_name) {
    // 1. Nettoyage
    $scan_output = str_replace(chr(0), '', $scan_output);
    $scan_output = preg_replace('/^[\r\n\s]*% Total.+$/m', '', $scan_output);
    $scan_output = utf8_encode($scan_output);
    if (strlen($scan_output) > 25000) $scan_output = substr($scan_output, 0, 25000) . "\n...[TRUNCATED]...";

    // 2. Contexte
    $specific_context = "Extrais les faits marquants.";
    switch (strtolower($tool_name)) {
        case 'port scan': case 'nmap': $specific_context = "Liste les ports ouverts, services et OS."; break;
        case 'whois': $specific_context = "Registrar, Dates clés."; break;
        case 'headers': $specific_context = "Serveur, Techno, Headers manquants."; break;
    }

    // 3. Prompt
    $prompt = "Tu es un expert en cybersécurité. Analyse ce rapport ($tool_name).
    CONTEXTE: $specific_context
    RAPPORT: $scan_output
    Réponds UNIQUEMENT avec ce JSON (RFC 8259, Français) :
    {
        \"summary\": \"Résumé 2 phrases.\",
        \"risk_score\": 50,
        \"risk_level\": \"Moyen\",
        \"key_findings\": [\"Fait 1\"],
        \"threats_detected\": [{\"title\":\"Titre\",\"severity\":\"High\",\"description\":\"Desc\",\"potential_impact\":\"Impact\",\"remediation\":\"Fix\"}]
    }";

    // 4. Préparation Données pour Python
    $payload = [
        "model" => "mistralai/devstral-2-123b-instruct-2512",
        "messages" => [["role" => "user", "content" => $prompt]],
        "temperature" => 0.15,
        "max_tokens" => 4096,
        "stream" => false
    ];

    // 5. Appel du Pont Python
    // On passe les données via un Pipe pour éviter les erreurs de shell
    $descriptorSpec = [
        0 => ["pipe", "r"],  // stdin (PHP écrit ici)
        1 => ["pipe", "w"],  // stdout (Python répond ici)
        2 => ["pipe", "w"]   // stderr (Erreurs)
    ];

    $process = proc_open('python3 ' . __DIR__ . '/../ai_bridge.py', $descriptorSpec, $pipes);

    if (is_resource($process)) {
        // Envoyer le JSON à Python
        fwrite($pipes[0], json_encode($payload));
        fclose($pipes[0]);

        // Lire la réponse de Python
        $response = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        
        // Lire les erreurs éventuelles
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        proc_close($process);

        if (!empty($errors)) {
            return json_encode(['error' => "Erreur Script Python: $errors"]);
        }
    } else {
        return json_encode(['error' => "Impossible de lancer le script Python."]);
    }

    // 6. Traitement Réponse
    $decoded = json_decode($response, true);
    $text_response = $decoded['choices'][0]['message']['content'] ?? null;

    if (!$text_response) {
        // Fallback si Python renvoie une erreur JSON
        if (isset($decoded['error'])) return json_encode(['error' => "API NVIDIA (via Python): " . $decoded['error']]);
        return json_encode(['error' => "Réponse vide du pont Python.", 'raw' => substr($response, 0, 200)]);
    }

    // Nettoyage Markdown
    $text_response = preg_replace('/^```json\s*/i', '', $text_response);
    $text_response = preg_replace('/^```\s*/i', '', $text_response);
    $text_response = preg_replace('/\s*```$/', '', $text_response);
    
    return trim($text_response);
}
?>
