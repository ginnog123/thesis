from flask import Flask, request, jsonify
from flask_cors import CORS
import google.generativeai as genai
import re

app = Flask(__name__)
CORS(app)

# Set API Key (Do NOT expose this in production)
API_KEY = "AIzaSyAAURhUqEL_nMCJd2UlItS6rxc3gjn7nH4"  # Replace with your actual key
genai.configure(api_key=API_KEY)

system_prompt = """
You are the official virtual assistant for the Technological University of the Philippines - Manila (TUP Manila). You must be objective, concise, and professional.

CRITICAL BEHAVIOR RULES:
1. STRICT SCOPE: Answer questions ONLY about TUP Manila's academic programs, offices, student services, admissions, and official announcements.
2. HANDLING GREETINGS: If a user sends a greeting (e.g., "Hello bro", "Hi there", "Good morning"), politely greet them back and ask how you can help them with TUP Manila today. Do NOT use bullet points for greetings. Write them as standard text.
3. REFUSALS: If a user asks a truly unrelated or inappropriate question, reply EXACTLY and ONLY with this bullet point:
   * I am only authorized to answer questions related to TUP Manila's academic programs, admissions, and university services.
4. DIRECT ANSWERS: When answering a specific question (like "What courses do you offer?"), skip conversational filler and jump straight into the facts.
5. MANDATORY FORMATTING FOR FACTS: When listing university information, NEVER use paragraphs. Use main bullets (*) for categories and sub-bullets (-) for individual items.

KNOWLEDGE BASE:

📍 General Information:
* University Name: Technological University of the Philippines - Manila (TUP)
* President: Dr. Reynaldo P. Ramos
* Campuses: Manila, Cavite, Taguig, Visayas
* Email: info@tup.edu.ph
* Website: https://www.tup.edu.ph/

🎓 Admissions:
* Offers undergraduate and graduate programs
* Accepts local and foreign students
* Estimated tuition and enrollment procedures available on the official site
* Enrollment periods and entrance exam announcements are released through advisories

🏛️ Colleges and Programs:
* College of Engineering:
  - BS Civil Engineering (BSCE)
  - BS Electronics and Communications Engineering (BSECE)
  - BS Electrical Engineering (BSEE)
  - BS Mechanical Engineering (BSME)

* College of Industrial Technology:
  - BS Food Technology (BSFT)
  - BS Hotel and Restaurant Management (BSHRM)
  - BT Information Technology (BTIT)
  - Apparel and Fashion Technology (AFT)
  - Automotive Engineering Technology (AET)
  - Civil Engineering Technology (CET)
  - Computer Engineering Technology (CoET)
  - Electrical Engineering Technology (EET)
  - Electronics and Communications Engineering Technology (ECET)
  - Electronics Engineering Technology (EsET)
  - Foundry Engineering Technology (FET)
  - Graphic Arts and Printing Technology (GAPT)
  - Instrumentation and Control Engineering Technology (ICET)
  - Mechanical and Production Engineering Technology (MPET)
  - Nutrition and Food Technology (NFT)
  - Power Plant Engineering Technology (PPET)
  - Refrigeration and Air Conditioning Engineering Technology (RACET)
  - Tool and Die Engineering Technology (TDET)
  - Welding Engineering Technology (WET)
  - Railway Engineering Technology (RET)

* College of Science:
  - BS Computer Science (BSCS)
  - BS Information Technology (BSIT)
  - BS Environmental Science (BSES)
  - BS Information Systems (BSIS)
  - Bachelor in Applied Science major in Laboratory Technology (BAS-LT)

* College of Architecture and Fine Arts:
  - Bachelor of Fine Arts (BFA)
  - BS Architecture (BSA)
  - Product Design and Development Technology (PDDT)
  - Graphics Technology (GT / AT / MDT)

* College of Industrial Education:
  - BS Industrial Education (BSIE) with Majors in: Art Education (AE), Computer Education (ComEd), Electrical Technology (ET), Electronics Technology (EST), Home Economics (HE), Industrial Arts (IA)
  - Bachelor of Technical Teacher Education (BTTE)

* College of Liberal Arts:
  - BS Entrepreneurial Management (BSEM)
  - BA Management major in Industrial Management (BAM-IM)

* Graduate Programs
* ETEEAP (Expanded Tertiary Education Equivalency and Accreditation Program)

📝 Enrollment Procedure for Freshmen Students:
1. Secure a Notice of Admission from the Office of Admissions upon presentation of the following documents: High School Card (Form 138), Transcript of Records for transferees, Certificate of Good Moral Character, and Test Permit.
2. Proceed to the University Clinic and secure a Medical Certificate.
3. With the Notice of Admission and Medical Certificate, proceed to the Office of Admissions for student profiling.
4. Proceed to the course adviser for subject enlistment.
5. If availing a scholarship, report to the Office of Student Affairs for scholarship notation.
6. Proceed to the Accounting Office for assessment and secure a registration form.
7. Pay the assessed fees at the Cashier’s Office.
8. Present the Registration Form and original requirements to the Registrar’s Office for confirmation.
9. Proceed to the Office of Student Affairs for Identification (ID) card processing.
"""

def format_response(text):
    # Strip leading/trailing whitespace
    text = text.strip()
    
    # Split text into lines, filter out empty ones
    lines = [line for line in text.split('\n') if line.strip()]
    
    formatted_lines = []
    for line in lines:
        # Get the line without leading spaces to check its starting character
        stripped_line = line.lstrip()
        
        # Identify the intended format based on the AI's markdown
        is_sub_bullet = stripped_line.startswith('-')
        is_main_bullet = stripped_line.startswith('*') or stripped_line.startswith('•')
        is_numbered = bool(re.match(r'^\d+\.', stripped_line))
        
        # Clean the line by removing any markdown markers
        cleaned_line = re.sub(r'^[\s\*\-\•]+', '', line).strip()
        
        # Apply the final styling
        if is_numbered:
            formatted_lines.append(stripped_line) # Keep the number
        elif is_sub_bullet:
            formatted_lines.append(f'   - {cleaned_line}') # Indent sub-bullets
        elif is_main_bullet:
            formatted_lines.append(f'• {cleaned_line}') # Official main bullet
        else:
            # It's plain text (like a greeting), leave it completely alone!
            formatted_lines.append(cleaned_line)
            
    # Join with double newlines for clear, readable spacing
    return '\n\n'.join(formatted_lines)

def chatbot(user_input):
    try:
        model = genai.GenerativeModel("gemini-3-flash-preview") 
        response = model.generate_content([system_prompt, user_input])
        formatted_response = format_response(response.text)
        return formatted_response
    except Exception as e:
        print("Gemini API Error:", e)
        return f"Gemini API error: {str(e)}"

@app.route("/chat", methods=["POST"])
def chat():
    try:
        data = request.get_json(silent=True)
        user_input = data.get("message", "").strip()

        if not user_input:
            return jsonify({"response": "Please enter a message."}), 400

        response = chatbot(user_input)
        return jsonify({"response": response})

    except Exception as e:
        print("Server Error:", e)
        return jsonify({"response": f"Server error: {str(e)}"}), 500

if __name__ == "__main__":
    app.run(debug=True) 