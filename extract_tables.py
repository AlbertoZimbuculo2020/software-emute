import sys

search_terms = ['paciente', 'medico', 'seguradora', 'agendamento', 'consulta', 'triagem', 'servico', 'artigo']
found_tables = {}

with open('emute.sql', 'r', encoding='utf-8', errors='ignore') as f:
    current_table = None
    for line in f:
        if 'CREATE TABLE IF NOT EXISTS' in line:
            parts = line.split('`')
            if len(parts) > 1:
                current_table = parts[1]
                for term in search_terms:
                    if term.lower() in current_table.lower():
                        found_tables[current_table] = []
                        break
        elif current_table in found_tables and '`' in line:
            parts = line.split('`')
            if len(parts) > 1:
                col_name = parts[1]
                if not col_name.isspace() and ' ' not in col_name:
                    found_tables[current_table].append(col_name)
        elif current_table and ');' in line:
            current_table = None

for table, columns in found_tables.items():
    print(f"Table: {table}")
    print(f"Columns: {', '.join(columns)}")
    print("-" * 20)
