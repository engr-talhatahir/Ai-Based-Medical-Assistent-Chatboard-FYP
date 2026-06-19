# app.py - Place this inside your local project folder (e.g., model_api/)
from flask import Flask, request, jsonify
from transformers import DistilBertTokenizer, DistilBertForSequenceClassification
import torch
import numpy as np
import os

app = Flask(__name__)

# Model aur Tokenizer ka path set karein (jo unzip kiye hain)
MODEL_DIR = "D:/wamp64/www/PHP/FYP/model_api"

print("⏳ Loading Trained Medical Intent Model...")
try:
    tokenizer = DistilBertTokenizer.from_pretrained(MODEL_DIR)
    model = DistilBertForSequenceClassification.from_pretrained(MODEL_DIR)
    model.eval()  # Model ko evaluation mode par set karna
    print("✅ Model & Tokenizer Loaded Successfully!")
except Exception as e:
    print(f"❌ Error loading model: {str(e)}")

@app.route('/predict', methods=['POST'])
def predict():
    data = request.get_json()
    if not data or 'message' not in data:
        return jsonify({'success': False, 'error': 'No message provided'}), 400
    
    user_message = data['message']
    
    try:
        # Text ko tokenize karna
        inputs = tokenizer(user_message, padding=True, truncation=True, max_length=128, return_tensors="pt")
        
        # Prediction lena
        with torch.no_grad():
            outputs = model(**inputs)
            logits = outputs.logits
            prediction_idx = torch.argmax(logits, dim=1).item()
            
        # Intent/Disease name aur confidence nikalna
        predicted_disease = model.config.id2label[prediction_idx]
        
        # Probabilities nikalna percentage ke liye
        probabilities = torch.nn.functional.softmax(logits, dim=1)
        confidence = probabilities[0][prediction_idx].item() * 100

        return jsonify({
            'success': True,
            'disease': predicted_disease,
            'confidence': f"{confidence:.2f}%"
        })
        
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

if __name__ == '__main__':
    # Port 5000 par local API run karna
    app.run(host='127.0.0.1', port=5000, debug=True)