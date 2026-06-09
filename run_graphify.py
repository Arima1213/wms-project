import os, sys, json
from pathlib import Path

# Import langsung dari submodule (lazy import via __init__ bermasalah di script)
from graphify.extract import collect_files, extract
from graphify.build import build_from_json
from graphify.cluster import cluster, score_all
from graphify.analyze import god_nodes, surprising_connections, suggest_questions
from graphify.report import generate as generate_report
from graphify.export import to_json, to_html

project_dir = r"C:\Users\ASUS\Downloads\docker-setup\wms-project"
output_dir = os.path.join(project_dir, "graphify-out")
os.makedirs(output_dir, exist_ok=True)

# ============================================================
# 1. COLLECT FILES
# ============================================================
print("[1/5] Collecting files...")
all_paths = collect_files(Path(project_dir))
print(f"  Total collected: {len(all_paths)}")

# Filter path components to exclude vendor, bootstrap, etc.
exclude_dirs = {"vendor", "bootstrap", "test-results", "playwright-report", "cache"}
paths = [p for p in all_paths if not any(part in exclude_dirs for part in p.parts)]
print(f"  After exclude filter: {len(paths)}")

# Breakdown
exts = {}
for p in paths:
    ext = p.suffix.lower()
    exts[ext] = exts.get(ext, 0) + 1
print("  Extensions:", dict(sorted(exts.items())))

# ============================================================
# 2. EXTRACT AST
# ============================================================
print(f"\n[2/5] Extracting AST from {len(paths)} files...")
extraction = extract(
    paths=paths,
    parallel=False,
)
nodes = extraction.get("nodes", [])
edges = extraction.get("edges", [])
print(f"  Nodes: {len(nodes)}")
print(f"  Edges: {len(edges)}")

# ============================================================
# 3. BUILD GRAPH
# ============================================================
print("\n[3/5] Building graph...")
G = build_from_json(extraction, root=project_dir)
print(f"  Graph: {G.number_of_nodes()} nodes, {G.number_of_edges()} edges")

# ============================================================
# 4. CLUSTER + ANALYZE
# ============================================================
print("\n[4/5] Clustering & analyzing...")
communities = cluster(G)
print(f"  Communities: {len(communities)}")

scores = score_all(G, communities)
print(f"  Cohesion scores computed")

# Community labels
community_labels = {}
for cid in communities:
    community_labels[cid] = f"Community {cid}"

# Analysis
god_nodes_list = god_nodes(G)
print(f"  God nodes: {len(god_nodes_list)}")

surprise_list = surprising_connections(G, communities)
print(f"  Surprising connections: {len(surprise_list)}")

questions = suggest_questions(G, communities, community_labels)
print(f"  Suggested questions: {len(questions)}")

# ============================================================
# 5. EXPORT
# ============================================================
print("\n[5/5] Exporting...")

# JSON
json_path = os.path.join(output_dir, "graph.json")
ok = to_json(G, communities, json_path, force=True)
print(f"  JSON: {json_path} ({"OK" if ok else "FAILED"})")

# HTML
html_path = os.path.join(output_dir, "graph.html")
to_html(G, communities, html_path, community_labels=community_labels)
html_size = os.path.getsize(html_path) / 1024
print(f"  HTML: {html_path} ({html_size:.0f} KB)")

# Markdown report
try:
    report = generate_report(
        G=G,
        communities=communities,
        cohesion_scores=scores,
        community_labels=community_labels,
        god_node_list=god_nodes_list,
        surprise_list=surprise_list,
        detection_result={},
        token_cost={},
        root=project_dir,
        suggested_questions=questions,
    )
    report_path = os.path.join(output_dir, "GRAPH_REPORT.md")
    with open(report_path, "w", encoding="utf-8") as f:
        f.write(report)
    print(f"  Report: {report_path}")
except Exception as e:
    print(f"  Report generation skipped: {e}")

print("\n=== SELESAI ===")
print(f"Graph: {G.number_of_nodes()} nodes, {G.number_of_edges()} edges, {len(communities)} communities")
print(f"Output folder: {output_dir}")
print(f"Buka graph.html di browser untuk lihat visualisasi!")
