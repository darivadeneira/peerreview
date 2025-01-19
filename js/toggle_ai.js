document.addEventListener('DOMContentLoaded', function () {
    var toggleAiButton = document.getElementById("toggle-ai-button");
    var otherButton = document.getElementById("id_submit");
    var title = document.getElementById("id_general");
    var table = document.getElementById("feedback-table");

    if (toggleAiButton && otherButton) {
        otherButton.style.display = "none";
        title.style.display = "none";

        // Crear un contenedor para los inputs y los botones
        var container = document.createElement("div");
        container.id = "ai-input-container";
        container.style.display = "flex";
        container.style.alignItems = "center";
        container.style.gap = "10px"; // Espaciado entre los elementos

        // Crear el campo de texto para la API Key
        var apiKeyInput = document.createElement("input");
        apiKeyInput.type = "text";
        apiKeyInput.id = "api-key-input";
        apiKeyInput.placeholder = "Ingrese su API Key";
        apiKeyInput.className = "form-control";

        // Crear el botón "Revisar ahora"
        var reviewButton = document.createElement("button");
        reviewButton.id = "review-button";
        reviewButton.textContent = "Revisar ahora";
        reviewButton.className = "btn btn-success";

        // Insertar el contenedor y añadir los elementos
        toggleAiButton.parentNode.insertBefore(container, toggleAiButton);
        container.appendChild(toggleAiButton);
        container.appendChild(apiKeyInput);
        container.appendChild(reviewButton);

        // Comprobar si la IA está activada al cargar la página
        if (localStorage.getItem("aiActivated") === "true") {
            toggleAiButton.textContent = "Desactivar revisión por IA";
            toggleAiButton.classList.remove("btn-primary");
            toggleAiButton.classList.add("btn-danger");
            apiKeyInput.style.display = "inline-block";
            reviewButton.style.display = "inline-block";

            // Mostrar la columna de "Revisión IA" en la tabla
            addAiColumnToTable();
        } else {
            toggleAiButton.textContent = "Activar revisión por IA";
            toggleAiButton.classList.remove("btn-danger");
            toggleAiButton.classList.add("btn-primary");
            apiKeyInput.style.display = "none";
            reviewButton.style.display = "none";

            // Eliminar la columna de "Revisión IA" en la tabla si no está activada
            removeAiColumnFromTable();
        }

        // Restaurar los valores de la API Key y URL si existen
        apiKeyInput.value = localStorage.getItem("apiKey") || '';

        // Manejar clic en el botón para mostrar/ocultar elementos
        toggleAiButton.addEventListener("click", function () {
            var button = document.getElementById("toggle-ai-button");
            var headerRow = table.tHead.rows[0];
            var rows = table.tBodies[0].rows;

            if (button.classList.contains("btn-primary")) {
                // Mostrar inputs y botón
                apiKeyInput.style.display = "inline-block";
                reviewButton.style.display = "inline-block";

                // Añadir la columna "Revisión IA" en la tabla
                addAiColumnToTable();

                // Cambiar botón a "Desactivar IA"
                button.textContent = "Desactivar revisión por IA";
                button.classList.remove("btn-primary");
                button.classList.add("btn-danger");

                // Guardar el estado en localStorage
                localStorage.setItem("aiActivated", "true");
            } else {
                // Ocultar inputs y botón
                apiKeyInput.style.display = "none";
                reviewButton.style.display = "none";

                // Eliminar la columna "Revisión IA" de la tabla
                removeAiColumnFromTable();

                // Cambiar botón a "Activar IA"
                button.textContent = "Activar revisión por IA";
                button.classList.remove("btn-danger");
                button.classList.add("btn-primary");

                // Guardar el estado en localStorage
                localStorage.setItem("aiActivated", "false");
            }
        });

        // Manejar clic en el botón "Revisar ahora"
        reviewButton.addEventListener("click", function (event) {
            event.preventDefault(); // Prevenir redirección
            var apiKey = apiKeyInput.value;
        
            if (!apiKey) {
                alert("Por favor, ingrese la API Key antes de enviar.");
                event.preventDefault(); // Prevenir redirección
                return;
            }
        
            // Guardar los valores de la API Key
            localStorage.setItem("apiKey", apiKey);
        
            var rows = table.tBodies[0].rows;
            var feedbackData = [];
        
            // Recopilar las respuestas de la tabla
            for (var i = 0; i < rows.length; i++) {
                var cells = rows[i].cells;
        
                // Recopilar los datos de cada fila
                var data = {
                    author: cells[1].textContent,
                    reviewer: cells[2].textContent,
                    feedbackauthor: cells[3].textContent,
                };
        
                feedbackData.push(data);  // Agregar la fila al array
            }
        
            // Realizar la petición a GPT-3 para evaluar los comentarios
            fetch("https://api.openai.com/v1/chat/completions", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Authorization": `Bearer ${apiKey}`,
                },
                body: JSON.stringify({
                    model: "gpt-4o-mini", 
                    messages: [
                        {
                            role: "system",
                            content: "Evalúa si esta retroalimentación corrige adecuadamente la respuesta del estudiante sobre el tema dado. Responde únicamente con 'Alineada' o 'Revisión'."
                        },
                        ...feedbackData.map(data => ({
                            role: "user",
                            content: `Comentario: ${data.feedbackauthor}`
                        }))
                    ],
                    temperature: 0.5,
                    max_tokens: 1000,
                }),
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Error en la solicitud: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.choices && data.choices[0].message) {
                    console.log("Evaluación exitosa:", data);
                    // Actualizar la columna "Revisión IA" con los resultados
                    for (var i = 0; i < data.choices.length; i++) {
                        rows[i].cells[rows[i].cells.length - 1].textContent =
                            data.choices[i].message.content.trim();
                    }
                    alert("Evaluación completada con éxito.");
                } else {
                    console.error("Error en la evaluación:", data.error);
                    alert("Error al procesar la evaluación de la IA.");
                }
            })
            .catch(error => {
                console.error("Error en el procesamiento de la solicitud:", error);
                alert("Ocurrió un error al intentar procesar la solicitud: " + error.message);
            });
        });
        
    }

    function addAiColumnToTable() {
        var headerRow = table.tHead.rows[0];
        var rows = table.tBodies[0].rows;

        // Añadir la columna "Revisión IA" en la tabla
        var newHeaderCell = document.createElement("th");
        newHeaderCell.textContent = "Revisión IA";
        headerRow.appendChild(newHeaderCell);

        // Añadir las celdas de "Pendiente" para cada fila
        for (var i = 0; i < rows.length; i++) {
            var newCell = document.createElement("td");
            newCell.textContent = "Pendiente";
            rows[i].appendChild(newCell);
        }
    }

    function removeAiColumnFromTable() {
        var headerRow = table.tHead.rows[0];
        var rows = table.tBodies[0].rows;

        // Eliminar la columna "Revisión IA" de la tabla
        headerRow.removeChild(headerRow.lastChild);

        for (var i = 0; i < rows.length; i++) {
            rows[i].removeChild(rows[i].lastChild);
        }
    }
});
