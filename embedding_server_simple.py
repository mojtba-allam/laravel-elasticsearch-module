from http.server import HTTPServer, BaseHTTPRequestHandler
import json
from sentence_transformers import SentenceTransformer

# Load model
print("Loading model...")
model = SentenceTransformer("sentence-transformers/all-MiniLM-L6-v2")
print("Model loaded successfully!")

class EmbeddingHandler(BaseHTTPRequestHandler):
    def do_POST(self):
        if self.path == '/embed':
            content_length = int(self.headers['Content-Length'])
            post_data = self.rfile.read(content_length)
            data = json.loads(post_data.decode('utf-8'))
            
            text = data.get('text', '')
            if not text:
                self.send_response(400)
                self.send_header('Content-type', 'application/json')
                self.end_headers()
                self.wfile.write(json.dumps({'error': 'Text is required'}).encode())
                return
            
            # Generate embedding
            vector = model.encode(text).tolist()
            
            self.send_response(200)
            self.send_header('Content-type', 'application/json')
            self.end_headers()
            response = {'embedding': vector}
            self.wfile.write(json.dumps(response).encode())
        else:
            self.send_response(404)
            self.end_headers()
    
    def do_GET(self):
        if self.path == '/':
            self.send_response(200)
            self.send_header('Content-type', 'application/json')
            self.end_headers()
            response = {
                'service': 'Embedding Service',
                'model': 'sentence-transformers/all-MiniLM-L6-v2',
                'dimensions': 384,
                'status': 'running'
            }
            self.wfile.write(json.dumps(response).encode())
        else:
            self.send_response(404)
            self.end_headers()

if __name__ == '__main__':
    server = HTTPServer(('0.0.0.0', 8000), EmbeddingHandler)
    print('Embedding service running on http://0.0.0.0:8000')
    server.serve_forever()
