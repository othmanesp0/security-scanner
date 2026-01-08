import sys
import json
import requests

# Configuration
API_KEY = "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"
URL = "https://integrate.api.nvidia.com/v1/chat/completions"

def analyze():
    # 1. Lire les données envoyées par PHP (via l'argument ou stdin)
    try:
        input_data = sys.stdin.read()
        if not input_data:
            print(json.dumps({"error": "Aucune donnée reçue par le script Python"}))
            return

        payload = json.loads(input_data)
    except Exception as e:
        print(json.dumps({"error": f"Erreur lecture JSON Python: {str(e)}"}))
        return

    # 2. Préparer la requête NVIDIA
    headers = {
        "Content-Type": "application/json",
        "Authorization": f"Bearer {API_KEY}"
    }

    # 3. Envoyer la requête (Utilise la connexion internet de l'utilisateur Kali)
    try:
        response = requests.post(URL, headers=headers, json=payload, timeout=60)
        
        # 4. Renvoyer la réponse brute à PHP
        print(response.text)
        
    except Exception as e:
        # En cas d'erreur réseau Python
        error_msg = {"choices": [{"message": {"content": f"Erreur Python Network: {str(e)}"}}]}
        print(json.dumps(error_msg))

if __name__ == "__main__":
    analyze()
