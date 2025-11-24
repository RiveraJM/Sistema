/**
 * JavaScript Principal del Sistema
 */

// Toggle del sidebar en móviles
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    // Cerrar sidebar al hacer clic fuera en móvil
    document.addEventListener('click', function(event) {
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                sidebar.classList.remove('active');
            }
        }
    });
});

// Función para confirmar eliminaciones
function confirmarEliminacion(mensaje) {
    return confirm(mensaje || '¿Está seguro de que desea eliminar este registro?');
}

// Función para mostrar alertas
function mostrarAlerta(mensaje, tipo = 'info') {
    const alertas = {
        'success': { icono: 'fa-check-circle', color: '#10B981' },
        'error': { icono: 'fa-exclamation-circle', color: '#EF4444' },
        'warning': { icono: 'fa-exclamation-triangle', color: '#F59E0B' },
        'info': { icono: 'fa-info-circle', color: '#00D4D4' }
    };
    
    const alerta = alertas[tipo] || alertas['info'];
    
    const div = document.createElement('div');
    div.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: white;
        padding: 15px 20px;
        border-radius: 10px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        gap: 12px;
        z-index: 9999;
        animation: slideInRight 0.3s ease;
        max-width: 350px;
    `;
    
    div.innerHTML = `
        <i class="fas ${alerta.icono}" style="font-size: 24px; color: ${alerta.color};"></i>
        <span style="flex: 1; font-size: 14px;">${mensaje}</span>
        <button onclick="this.parentElement.remove()" style="background: none; border: none; cursor: pointer; color: #999;">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    document.body.appendChild(div);
    
    setTimeout(() => {
        div.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => div.remove(), 300);
    }, 5000);
}

// Función para validar formularios
function validarFormulario(formularioId) {
    const form = document.getElementById(formularioId);
    if (!form) return false;
    
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let valido = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = '#EF4444';
            valido = false;
        } else {
            input.style.borderColor = '';
        }
    });
    
    if (!valido) {
        mostrarAlerta('Por favor, complete todos los campos requeridos', 'error');
    }
    
    return valido;
}

// Función para formatear fechas
function formatearFecha(fecha) {
    const opciones = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(fecha).toLocaleDateString('es-ES', opciones);
}

// Función para formatear hora
function formatearHora(hora) {
    return hora.substring(0, 5);
}

// Búsqueda en tiempo real
function inicializarBusqueda(inputId, tablaId) {
    const input = document.getElementById(inputId);
    const tabla = document.getElementById(tablaId);
    
    if (input && tabla) {
        input.addEventListener('keyup', function() {
            const filtro = this.value.toLowerCase();
            const filas = tabla.getElementsByTagName('tr');
            
            for (let i = 1; i < filas.length; i++) {
                const fila = filas[i];
                const texto = fila.textContent.toLowerCase();
                
                if (texto.includes(filtro)) {
                    fila.style.display = '';
                } else {
                    fila.style.display = 'none';
                }
            }
        });
    }
}

// Búsqueda con autocompletado
document.addEventListener('DOMContentLoaded', function() {
    const searchInputs = document.querySelectorAll('input[name="q"]');
    
    searchInputs.forEach(input => {
        let timeout = null;
        let resultsDiv = null;
        
        input.addEventListener('input', function() {
            clearTimeout(timeout);
            
            const query = this.value.trim();
            
            if (query.length < 2) {
                if (resultsDiv) resultsDiv.remove();
                return;
            }
            
            timeout = setTimeout(() => {
                fetch(`api/busqueda-rapida.php?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        mostrarResultadosRapidos(data.resultados, input);
                    });
            }, 300);
        });
        
        // Cerrar resultados al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-autocomplete')) {
                if (resultsDiv) resultsDiv.remove();
            }
        });
    });
});

function mostrarResultadosRapidos(resultados, input) {
    // Remover resultados anteriores
    const prevResults = document.querySelector('.search-results-dropdown');
    if (prevResults) prevResults.remove();
    
    if (resultados.length === 0) return;
    
    const dropdown = document.createElement('div');
    dropdown.className = 'search-results-dropdown';
    dropdown.style.cssText = `
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        margin-top: 5px;
        max-height: 300px;
        overflow-y: auto;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        z-index: 1000;
    `;
    
    resultados.forEach(resultado => {
        const item = document.createElement('div');
        item.style.cssText = `
            padding: 12px 15px;
            cursor: pointer;
            border-bottom: 1px solid var(--border-color);
            transition: background 0.2s;
        `;
        
        const icon = resultado.tipo === 'paciente' ? 'fa-user' : 'fa-user-md';
        const color = resultado.tipo === 'paciente' ? '#10B981' : '#6366F1';
        
        item.innerHTML = `
            <div style="display: flex; align-items: center; gap: 12px;">
                <i class="fas ${icon}" style="color: ${color}; font-size: 18px;"></i>
                <div style="flex: 1;">
                    <div style="font-weight: 500;">${resultado.nombre}</div>
                    <div style="font-size: 12px; color: var(--text-secondary);">
                        ${resultado.dni || resultado.especialidad || ''}
                    </div>
                </div>
                <i class="fas fa-arrow-right" style="color: var(--text-secondary); font-size: 12px;"></i>
            </div>
        `;
        
        item.addEventListener('mouseenter', () => {
            item.style.background = 'var(--background-main)';
        });
        
        item.addEventListener('mouseleave', () => {
            item.style.background = 'white';
        });
        
        item.addEventListener('click', () => {
            if (resultado.tipo === 'paciente') {
                window.location.href = `historia-clinica.php?paciente_id=${resultado.id}`;
            } else {
                window.location.href = `medicos.php?id=${resultado.id}`;
            }
        });
        
        dropdown.appendChild(item);
    });
    
    const parent = input.parentElement;
    parent.style.position = 'relative';
    parent.appendChild(dropdown);
}
// Agregar animaciones CSS necesarias
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);