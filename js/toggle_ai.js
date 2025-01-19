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
        // Manejar clic en el botón "Revisar ahora"
        reviewButton.addEventListener("click", function (event) {
            var apiKey = apiKeyInput.value;

            if (!apiKey) {
                alert("Por favor, ingrese tanto la API Key antes de enviar.");
                event.preventDefault(); // Prevenir redirección
                return;
            }

            // Guardar los valores de la API Key y URL
            localStorage.setItem("apiKey", apiKey);

            var rows = table.tBodies[0].rows;
            var feedbackData = [];

            // Recopilar las respuestas de la tabla
            for (var i = 0; i < rows.length; i++) {
 // Suponiendo que las respuestas están en la columna 2
                feedbackData.push({ assessmentid: i + 1 });
            }

            // Enviar datos al archivo PHP
            fetch("eval/peerreview/evaluate_feedback_ai.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    apiKey: apiKey
                }),
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Actualizar la columna "Revisión IA" con los resultados
                        for (var i = 0; i < data.evaluation.length; i++) {
                            rows[i].cells[rows[i].cells.length - 1].textContent =
                                data.evaluation[i].review;
                        }
                        alert("Evaluación completada con éxito.");
                    } else {
                        alert("Error al procesar la evaluación de la IA: " + data.error);
                    }
                })
                .catch(error => {
                    console.error("Error al enviar datos al archivo PHP:", error);
                    alert("Ocurrió un error al intentar procesar la solicitud.");
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
