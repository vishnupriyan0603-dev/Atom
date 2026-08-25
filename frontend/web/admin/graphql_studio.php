<?php
// ATOM Web Admin — Phase 83: Autonomous GraphQL AST Query Complexity Analyzer & Depth Limiter
$pageTitle = "GraphQL Guard (Phase 83)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #E11D48;">GraphQL Query Complexity Guard</h2>
        <p class="text-muted small mb-0">Phase 83: AST Nesting Depth Limiter, Connection Cost Multipliers &amp; Recursive DoS Query Shield</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-danger text-white fw-bold" onclick="analyzeGraphQLQuery()">
            <i class="bi bi-shield-check me-1"></i> Analyze Query Cost
        </button>
    </div>
</div>

<!-- GraphQL Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">AST NESTING DEPTH</div>
            <div class="fs-4 fw-bold text-rose-400" id="metricDepth" style="color: #FB7185;">3 / 7 DEPTH</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">CALCULATED COMPLEXITY</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricCost" style="color: #34D399;">32 / 250 COST</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">POLICY STATUS</div>
            <div class="fs-4 fw-bold text-success" id="metricStatus">ALLOWED</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">DoS SHIELD</div>
            <div class="fs-4 fw-bold text-info">Zero-Cycle Safe</div>
        </div>
    </div>
</div>

<!-- GraphQL Sandbox & Analysis Matrix -->
<div class="row g-4 mb-4">
    <div class="col-md-7">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold fs-6"><i class="bi bi-code-square text-rose-400 me-2"></i>GraphQL Query Input</span>
                <span class="badge bg-secondary">AST PARSER</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">GRAPHQL DOCUMENT</label>
                    <textarea id="queryInput" class="form-control bg-black text-white border-secondary small font-monospace" rows="8">query FetchUserOrders {
  user(id: "usr_998877") {
    id
    name
    email
    orders(first: 10) {
      id
      total
      items {
        id
        sku
        quantity
      }
    }
  }
}</textarea>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-danger text-white fw-bold flex-grow-1" onclick="analyzeGraphQLQuery()">
                        <i class="bi bi-play-circle-fill me-1"></i> Check Query Complexity Budget
                    </button>
                    <button class="btn btn-sm btn-outline-warning" onclick="loadMaliciousBombQuery()">
                        <i class="bi bi-radioactive me-1"></i> Test Recursive Bomb
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-rose-400"><i class="bi bi-bar-chart-steps me-2"></i>Budget &amp; Cost Breakdown</span>
            </div>
            <div class="card-body">
                <div class="p-3 bg-black rounded border border-secondary mb-3 text-xs" id="analysisResultBox">
                    <div class="text-emerald-400 fw-bold mb-1">[READY] Click 'Check Query Complexity' to evaluate...</div>
                    <div class="text-muted">Enforces maximum depth ($\le 7$) and query complexity ($\le 250$).</div>
                </div>

                <div class="text-muted text-xs">
                    <div class="fw-bold text-white mb-1"><i class="bi bi-info-circle-fill text-rose-400 me-1"></i>Cost Weights:</div>
                    <div>&bull; Scalar Field: <strong>1 credit</strong></div>
                    <div>&bull; List / Connection Field: <strong>10 credits</strong></div>
                    <div>&bull; AST Depth Multiplier: <strong>5 &times; depth</strong></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function analyzeGraphQLQuery() {
    const q = document.getElementById('queryInput').value.trim();

    try {
        const res = await apiFetch('/api/graphql/analyze', {
            method: 'POST',
            body: JSON.stringify({ query: q })
        });

        if (res && res.success) {
            const d = res.data;
            document.getElementById('metricDepth').innerText = `${d.max_depth} / ${d.max_allowed_depth} DEPTH`;
            document.getElementById('metricCost').innerText = `${d.calculated_complexity} / ${d.max_allowed_complexity} COST`;
            document.getElementById('metricStatus').innerText = d.allowed ? 'ALLOWED' : 'BLOCKED';
            document.getElementById('metricStatus').className = `fs-4 fw-bold ${d.allowed ? 'text-success' : 'text-danger'}`;

            const badge = d.allowed 
                ? `<span class="badge bg-success">QUERY ALLOWED</span>` 
                : `<span class="badge bg-danger">BLOCKED: ${escapeHtml(d.rejection_reason)}</span>`;

            document.getElementById('analysisResultBox').innerHTML = `
                <div class="mb-2">${badge}</div>
                <div class="text-white"><strong>AST Max Depth:</strong> ${d.max_depth} (Limit: ${d.max_allowed_depth})</div>
                <div class="text-white"><strong>Total Tokens/Fields:</strong> ${d.field_count}</div>
                <div class="text-white"><strong>Total Complexity Score:</strong> ${d.calculated_complexity} (Limit: ${d.max_allowed_complexity})</div>
            `;

            if (typeof showToast === 'function') {
                showToast(`GraphQL Query: ${d.allowed ? 'Allowed' : 'Blocked'} (Cost: ${d.calculated_complexity})`, d.allowed ? 'success' : 'danger');
            }
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Analyze error: ' + e.message, 'error');
    }
}

function loadMaliciousBombQuery() {
    document.getElementById('queryInput').value = `query MaliciousRecursiveBomb {
  author {
    books {
      author {
        books {
          author {
            books {
              author {
                books {
                  author {
                    books {
                      title
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
  }
}`;
    analyzeGraphQLQuery();
}

document.addEventListener('DOMContentLoaded', () => {
    analyzeGraphQLQuery();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
