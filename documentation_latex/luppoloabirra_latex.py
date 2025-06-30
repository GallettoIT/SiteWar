import os
import re
import subprocess
import hashlib
import shutil
import json
import tempfile
from pathlib import Path

def generate_filename(code, output_dir, extension='png'):
    """
    Genera un nome file univoco basato sull'hash del codice Mermaid.
    """
    code_hash = hashlib.md5(code.encode('utf-8')).hexdigest()
    return os.path.join(output_dir, f"mermaid_{code_hash}.{extension}")

def get_diagram_type_and_dimensions(code):
    """
    Determina il tipo di diagramma e le dimensioni ottimali.
    Restituisce (tipo_diagramma, width_px, height_px, larghezza_tex, caption)
    """
    # Estrai il tipo di diagramma
    type_match = re.search(r'^(flowchart|sequenceDiagram|classDiagram|stateDiagram|gantt|pie|graph|erDiagram)', code.strip(), re.MULTILINE)
    
    if not type_match:
        return "generic", 1600, 1200, "0.95\\textwidth", "Diagramma"
    
    diagram_type = type_match.group(1)
    
    # Estrai una possibile didascalia/titolo dal diagramma
    caption = "Diagramma"
    
    # Valori predefiniti con dimensioni aumentate
    width_tex = "0.95\\textwidth"  # Larghezza massima quasi fino ai margini per default
    
    # Usa dimensioni in pixel MOLTO più grandi per alta qualità
    width_px = 1600  # Base width (alta risoluzione)
    height_px = 1200  # Base height (alta risoluzione)
    
    # Valuta la complessità del diagramma contando nodi/elementi
    lines = code.count("\n")
    elements = len(re.findall(r'(-->|==>|---|===)', code))
    
    # Regola dimensioni in base a tipo e complessità
    if diagram_type == "flowchart" or diagram_type == "graph":
        if "LR" in code or "RL" in code:  # Orizzontale
            width_px = 2000 if elements > 10 else 1800  
            height_px = 1000 if elements > 10 else 800
            width_tex = "1.0\\textwidth" if elements > 10 else "0.98\\textwidth"
        elif "TD" in code or "BT" in code:  # Verticale
            width_px = 1500 if elements > 10 else 1300
            height_px = 1800 if elements > 10 else 1400
            width_tex = "0.9\\textwidth" if elements > 10 else "0.85\\textwidth"
    elif diagram_type == "sequenceDiagram":
        width_px = 1800 if lines > 15 else 1600
        height_px = 1200 if lines > 15 else 1000
        width_tex = "1.0\\textwidth" if lines > 15 else "0.95\\textwidth"
    elif diagram_type == "classDiagram":
        width_px = 1700
        height_px = 1400
        width_tex = "0.98\\textwidth"  # Quasi full width
    elif diagram_type == "gantt":
        width_px = 1900
        height_px = 1000
        width_tex = "1.0\\textwidth"  # Full width per Gantt charts
    elif diagram_type == "pie":
        width_px = 1200
        height_px = 1200
        width_tex = "0.7\\textwidth"  # Pie charts possono essere più piccoli
    
    return diagram_type, width_px, height_px, width_tex, caption

def create_mermaid_config():
    """
    Crea un file di configurazione temporaneo per Mermaid con margini
    minimi e ottimizzato per la leggibilità.
    """
    config = {
        "theme": "default",
        "themeVariables": {
            # Dimensioni aumentate per migliore leggibilità
            "fontSize": "20px",
            "fontFamily": "arial",
            
            # Colori ottimizzati per il contrasto
            "primaryColor": "#0066cc",
            "primaryTextColor": "#ffffff",
            "primaryBorderColor": "#004d99",
            "lineColor": "#333333",
            "secondaryColor": "#006633",
            "tertiaryColor": "#ffffff",
            
            # Maggiore spessore delle linee per visibilità migliore
            "strokeWidth": "2px",
            "mainBkg": "#ffffff",
            "secondaryBkg": "#f8f8f8",
            "mainContrastColor": "#333333",
            "darkTextColor": "#333333",
            "border1": "#c9c9c9",
            "border2": "#aaaaaa",
            "arrowheadColor": "#333333",
            
            # Variabili specifiche per flowchart
            "nodeBorder": "#333333",
            "clusterBkg": "#ffffde",
            "clusterBorder": "#aaaaaa",
            "defaultLinkColor": "#333333",
            "titleColor": "#333333",
            "edgeLabelBackground": "#ffffff",
            
            # Variabili per sequence diagram
            "actorBkg": "#e6f3ff",
            "actorBorder": "#2980b9",
            "actorTextColor": "#333333",
            "actorLineColor": "#333333",
            "signalColor": "#333333",
            "signalTextColor": "#333333",
            "labelBoxBkgColor": "#e6f3ff",
            "labelBoxBorderColor": "#2980b9",
            "labelTextColor": "#333333",
            "noteBkgColor": "#fff5ad",
            "noteBorderColor": "#d9b800",
            "noteTextColor": "#333333",
        },
        "flowchart": {
            "htmlLabels": True,
            "curve": "linear",
            # Riduzione significativa di margini e padding
            "diagramPadding": 2,    # Era 10
            "nodeSpacing": 40,      # Era 50
            "rankSpacing": 60,      # Era 70
            "padding": 5            # Era 15
        },
        "sequence": {
            # Riduzione di margini per compattare il diagramma
            "diagramMarginX": 15,   # Era 50
            "diagramMarginY": 10,   # Era 30
            "actorMargin": 80,      # Era 100
            "width": 150,
            "height": 65,
            "boxMargin": 5,         # Era 10
            "boxTextMargin": 5,     # Era 10
            "noteMargin": 10,       # Era 15
            "messageMargin": 35,    # Era 40
            "messageAlign": "center",
            "mirrorActors": True,
            "bottomMarginAdj": 5,   # Era 10
            "useMaxWidth": True
        },
        "er": {
            "entityPadding": 10,    # Era 20
            "strokeWidth": 2
        }
    }
    
    config_file = tempfile.NamedTemporaryFile(delete=False, suffix='.json')
    with open(config_file.name, 'w') as f:
        json.dump(config, f)
    
    return config_file.name

def process_mermaid_blocks_in_content(tex_content, output_dir, base_dir):
    """
    Cerca i blocchi di codice Mermaid e li converte in PNG ad alta qualità,
    ottimizzati per occupare più spazio nella pagina.
    """
    pattern = re.compile(r"```mermaid\s+(.*?)```", re.DOTALL)
    
    # Assicurati che la directory di output esista
    os.makedirs(output_dir, exist_ok=True)
    
    # Conta il numero di diagrammi nel file
    diagrams_count = len(pattern.findall(tex_content))
    print(f"Trovati {diagrams_count} diagrammi mermaid da elaborare")
    
    # Crea configurazione mermaid
    config_file = create_mermaid_config()
    
    def repl(match):
        code = match.group(1).strip()
        
        # Analizza il diagramma per determinare il tipo e le dimensioni ottimali
        diagram_type, width_px, height_px, width_tex, caption = get_diagram_type_and_dimensions(code)
        
        # Genera i file temporanei
        png_file = generate_filename(code, output_dir, extension="png")
        temp_input = generate_filename(code, output_dir, extension="mmd")
        
        # Calcola il nome del file relativo senza percorso
        png_filename = os.path.basename(png_file)
        
        # Salva il codice in un file temporaneo .mmd
        with open(temp_input, 'w', encoding='utf-8') as temp_f:
            temp_f.write(code)
            
        print(f"Generazione {diagram_type} in PNG ottimizzato: {png_filename}")
        
        try:
            # Genera PNG con margini minimi e alta risoluzione
            cmd = [
                "mmdc", 
                "-i", temp_input, 
                "-o", png_file, 
                "-b", "white",      # Sfondo bianco per migliore contrasto
                "-c", config_file,
                "-w", str(width_px),
                "-H", str(height_px)
            ]
            
            result = subprocess.run(cmd, check=False, capture_output=True, text=True)
            
            if result.returncode != 0 or not os.path.exists(png_file):
                print("Primo tentativo fallito, riprovo con impostazioni alternative...")
                # Riprova con diverse impostazioni
                retry_cmd = [
                    "mmdc", 
                    "-i", temp_input, 
                    "-o", png_file,
                    "-b", "white"
                ]
                retry_result = subprocess.run(retry_cmd, check=False, capture_output=True, text=True)
                
                if retry_result.returncode != 0 or not os.path.exists(png_file):
                    print(f"Tutti i tentativi falliti per {png_filename}")
                    return "\\begin{center}\\fbox{\\parbox{0.9\\textwidth}{\\centering Diagramma Mermaid non generato}}\\end{center}"
                else:
                    print(f"Generazione riuscita con impostazioni alternative per {png_filename}")
            
            # Calcola il percorso relativo dell'immagine
            relative_path = os.path.relpath(png_file, base_dir)
            
            # Pulisci i file temporanei
            if os.path.exists(temp_input):
                os.remove(temp_input)
            
            # Adatta le opzioni di posizionamento in base al tipo di diagramma
            # Qui usiamo sempre [H] per flowchart e altri diagrammi che hanno bisogno
            # di stare esattamente nel contesto, ma [!htb] per sequence diagram che
            # possono richiedere più spazio
            placement_option = "H"  # Default: qui esattamente
            if diagram_type in ["sequenceDiagram", "classDiagram"] and diagrams_count > 3:
                placement_option = "!htb"  # Più flessibile per diagrammi complessi, priorità maggiore
            
            # Costruisci l'ambiente figure ottimizzato per massimizzare lo spazio disponibile
            # Rimuoviamo l'opzione keepaspectratio per permettere alla figura di espandersi
            # fino alla dimensione specificata
            figure_env = f"""
\\begin{{figure}}[{placement_option}]
  \\centering
  \\setlength{{\\fboxsep}}{{1pt}}%
  \\includegraphics[width={width_tex}]{{{relative_path}}}
  \\caption{{{caption}}}
\\end{{figure}}
"""
            return figure_env.strip()
        except Exception as e:
            print(f"Eccezione nella generazione del diagramma: {str(e)}")
            return "\\begin{center}\\fbox{\\parbox{0.9\\textwidth}{\\centering Errore nella generazione del diagramma Mermaid}}\\end{center}"
    
    # Sostituisci tutti i blocchi mermaid
    result = pattern.sub(repl, tex_content)
    
    # Pulisci il file di configurazione
    if os.path.exists(config_file):
        os.remove(config_file)
        
    return result

def modify_preamble(main_tex_path):
    """
    Modifica il preambolo del file LaTeX per ottimizzare il layout
    e massimizzare lo spazio disponibile per le figure.
    """
    with open(main_tex_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Cerca se alcuni pacchetti sono già inclusi
    float_included = "\\usepackage{float}" in content
    graphicx_included = "\\usepackage{graphicx}" in content
    
    # Prepara le modifiche da aggiungere
    additions = []
    if not float_included:
        additions.append("\\usepackage{float}  % Per il posizionamento preciso delle figure")
    if not graphicx_included:
        additions.append("\\usepackage{graphicx}  % Per il controllo avanzato delle immagini")
    
    # Aggiungi configurazioni per migliorare il layout delle figure
    additions.extend([
        "\\renewcommand{\\figurename}{Figura}  % Personalizza etichetta figure",
        "\\setlength{\\textfloatsep}{5pt}  % Riduce lo spazio prima/dopo le figure (era 8pt)",
        "\\setlength{\\floatsep}{5pt}  % Riduce lo spazio tra le figure consecutive (era 8pt)",
        "\\setlength{\\intextsep}{5pt}  % Riduce lo spazio tra testo e figure (era 8pt)",
        "\\setlength{\\abovecaptionskip}{4pt}  % Spazio sopra la didascalia (era 6pt)",
        "\\setlength{\\belowcaptionskip}{2pt}  % Spazio sotto la didascalia (era 4pt)",
        "\\renewcommand{\\topfraction}{0.9}  % Permette figure più grandi in cima alla pagina (era 0.85)",
        "\\renewcommand{\\bottomfraction}{0.8}  % Permette figure più grandi in fondo alla pagina (era 0.75)",
        "\\renewcommand{\\textfraction}{0.1}  % Riduce la quantità minima di testo in una pagina (era 0.15)",
        "\\renewcommand{\\floatpagefraction}{0.75}  % Richiede figure/tabelle più piene sulle pagine float (era 0.7)",
        "\\setcounter{totalnumber}{3}  % Aumenta il numero massimo di float per pagina",
        "\\setcounter{topnumber}{2}  % Aumenta il numero di float nella parte superiore",
        "\\setcounter{bottomnumber}{2}  % Aumenta il numero di float nella parte inferiore"
    ])
    
    # Se ci sono modifiche da fare, inseriscile prima di \begin{document}
    if additions:
        modifications = "\n% Configurazioni aggiunte automaticamente per ottimizzare il layout delle figure\n"
        modifications += "\n".join(additions) + "\n"
        
        # Trova la posizione di \begin{document}
        begin_doc_pos = content.find("\\begin{document}")
        if begin_doc_pos != -1:
            # Inserisci le modifiche prima di \begin{document}
            modified_content = content[:begin_doc_pos] + modifications + content[begin_doc_pos:]
            
            # Scrivi il file modificato
            with open(main_tex_path, 'w', encoding='utf-8') as f:
                f.write(modified_content)
            
            print(f"Preambolo del file {main_tex_path} modificato con successo per ottimizzare il layout.")
        else:
            print(f"Avviso: \\begin{{document}} non trovato in {main_tex_path}, preambolo non modificato.")

def process_all_tex_files(root_dir, output_dir, project_copy_dir):
    """
    Elabora ricorsivamente tutti i file .tex in root_dir sostituendo i blocchi Mermaid.
    """
    for dirpath, _, filenames in os.walk(root_dir):
        for filename in filenames:
            if filename.endswith(".tex"):
                file_path = os.path.join(dirpath, filename)
                print(f"Elaborazione del file {file_path}...")
                try:
                    with open(file_path, 'r', encoding='utf-8') as f:
                        content = f.read()
                    new_content = process_mermaid_blocks_in_content(content, output_dir, project_copy_dir)
                    with open(file_path, 'w', encoding='utf-8') as f:
                        f.write(new_content)
                except Exception as e:
                    print(f"Errore nell'elaborazione del file {filename}: {str(e)}")

def copy_project(src, dst, exclude_dirs=[]):
    """
    Copia il progetto sorgente in dst, escludendo le directory specificate in exclude_dirs.
    """
    for item in os.listdir(src):
        s = os.path.join(src, item)
        d = os.path.join(dst, item)
        if os.path.isdir(s) and item in exclude_dirs:
            continue  # Salta le directory da escludere
        if os.path.isdir(s):
            shutil.copytree(s, d, dirs_exist_ok=True)
        else:
            shutil.copy2(s, d)

def check_mermaid_cli():
    """
    Verifica che mmdc (Mermaid CLI) sia installato e funzionante.
    Restituisce True se funziona, False altrimenti.
    """
    try:
        result = subprocess.run(["mmdc", "--version"], capture_output=True, text=True, check=False)
        if result.returncode == 0:
            print(f"Mermaid CLI (mmdc) trovato: {result.stdout.strip()}")
            return True
        else:
            print("Mermaid CLI (mmdc) non funziona correttamente.")
            return False
    except FileNotFoundError:
        print("ERRORE: mermaid-cli (mmdc) non è installato o non è nel PATH.")
        print("Installa mermaid-cli con: npm install -g @mermaid-js/mermaid-cli")
        return False

def optimize_layout_post_process(main_tex_path):
    """
    Esegue una post-elaborazione del file .tex principale per ottimizzare ulteriormente
    il layout, aggiungendo comandi per evitare interruzioni di pagina indesiderate.
    """
    with open(main_tex_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Evita che sezioni siano seguite immediatamente da un'interruzione di pagina
    # e che le figure rimangano sole
    modified = re.sub(
        r'(\\section{[^}]+})\s*\\begin{figure}',
        r'\1\n\\nopagebreak\n\\begin{figure}',
        content
    )
    
    # Evita che le figure siano separate dal loro testo correlato
    modified = re.sub(
        r'(\\subsection{[^}]+})\s*\\begin{figure}',
        r'\1\n\\nopagebreak\n\\begin{figure}',
        modified
    )
    
    with open(main_tex_path, 'w', encoding='utf-8') as f:
        f.write(modified)
    
    print("Post-elaborazione del layout completata con successo.")

def main():
    # Imposta il percorso del progetto
    project_dir = os.getcwd()
    main_tex = "main.tex"
    output_pdf = "documentazione_finale.pdf"
    
    # Verifica che mmdc sia installato
    if not check_mermaid_cli():
        print("Non è possibile procedere senza Mermaid CLI.")
        return
    
    # Crea una cartella temporanea nel progetto per i processi
    temp_build_dir = os.path.join(project_dir, "temp_build")
    if os.path.exists(temp_build_dir):
        shutil.rmtree(temp_build_dir)  # Pulisce la cartella se esiste già
    os.makedirs(temp_build_dir)

    # Crea le sottocartelle per i vari processi
    project_temp = os.path.join(temp_build_dir, "project_copy")
    os.makedirs(project_temp, exist_ok=True)

    # Crea la cartella per le immagini PNG all'interno della cartella del progetto temporaneo
    png_dir = os.path.join(project_temp, "diagram_images")
    os.makedirs(png_dir, exist_ok=True)

    pdf_output_dir = os.path.join(temp_build_dir, "pdf_output")
    os.makedirs(pdf_output_dir, exist_ok=True)
    
    # Copia il progetto, escludendo la cartella temporanea
    print(f"Creazione della copia temporanea del progetto in {project_temp}...")
    copy_project(project_dir, project_temp, exclude_dirs=["temp_build"])
    
    # Modifica il preambolo del file principale per ottimizzare il layout
    main_tex_path = os.path.join(project_temp, main_tex)
    modify_preamble(main_tex_path)
    
    # Elabora tutti i file .tex nella copia temporanea
    process_all_tex_files(project_temp, png_dir, project_temp)
    
    # Applica ottimizzazioni aggiuntive al layout
    optimize_layout_post_process(main_tex_path)

    # Compila il file principale con pdflatex (tre volte per garantire riferimenti corretti)
    for compilation in range(3):
        try:
            print(f"\nAvvio compilazione LaTeX {compilation+1}/3...")
            result = subprocess.run(
                ["pdflatex", "-interaction=nonstopmode", "-shell-escape", main_tex], 
                cwd=project_temp, check=False, capture_output=True, text=True
            )
            
            # Mostra l'output di pdflatex indipendentemente dal successo
            if result.returncode != 0:
                print(f"\nERRORE nella compilazione {compilation+1}.")
                if result.stderr:
                    print("Errori specifici:")
                    print(result.stderr)
                error_lines = [line for line in result.stdout.split('\n') if 'error' in line.lower()]
                if error_lines:
                    print("\nErrori rilevati:")
                    for line in error_lines[:10]:  # Mostra solo i primi 10 errori
                        print(f" - {line.strip()}")
            else:
                print(f"\nCompilazione {compilation+1} completata con successo.")
                
        except Exception as e:
            print(f"Eccezione durante la compilazione LaTeX: {str(e)}")
            return

    # Copia il PDF finale nella directory di output
    final_pdf_path = os.path.join(project_temp, main_tex.replace(".tex", ".pdf"))
    if os.path.exists(final_pdf_path):
        shutil.copy(final_pdf_path, os.path.join(pdf_output_dir, output_pdf))
        print(f"PDF generato correttamente: {os.path.join(pdf_output_dir, output_pdf)}")
        # Copia anche nella directory principale per comodità
        shutil.copy(final_pdf_path, os.path.join(project_dir, output_pdf))
        print(f"PDF copiato anche nella directory principale: {os.path.join(project_dir, output_pdf)}")
    else:
        print("Errore: PDF non generato.")

if __name__ == "__main__":
    main()