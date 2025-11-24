<!-- Agregar después del título en el header -->
<div style="flex: 1; max-width: 500px; margin: 0 20px;">
    <form method="GET" action="buscar.php" style="position: relative;">
        <input type="text" 
               name="q" 
               placeholder="Buscar pacientes, citas, médicos..." 
               style="width: 100%; padding: 10px 40px 10px 15px; border: 1px solid var(--border-color); border-radius: 25px; font-size: 14px;"
               autocomplete="off">
        <button type="submit" 
                style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); background: var(--primary-color); color: white; border: none; width: 35px; height: 35px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-search"></i>
        </button>
    </form>
</div>