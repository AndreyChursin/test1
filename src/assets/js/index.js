document.addEventListener('DOMContentLoaded', function () {
    // Если в localStorage нет данных, используется объект defaultCoordinates
    let storedCoordinates = localStorage.getItem('defaultCoordinates');
    if (storedCoordinates) {
        try {
            storedCoordinates = JSON.parse(storedCoordinates);
        } catch (e) {
            console.error('Ошибка парсинга данных из localStorage, используются данные по умолчанию');
        }
    }
    if (!storedCoordinates) {
        fetch('controller.php')
            .then(res => res.json())
            .then(serverCoords => {
                storedCoordinates = serverCoords;
                // Устанавливаем значения, как показано выше
            })
            .catch(err => {
                console.error('Ошибка загрузки данных с сервера:', err);
                storedCoordinates = defaultCoordinates;
            });
    }

    // Значения по умолчанию для координат опорных точек
    if (!storedCoordinates) {
        storedCoordinates = {
            point1: { lat: 50.110889, lon: 8.682139 },
            point2: { lat: 39.048111, lon: -77.472806 },
            point3: { lat: 45.849100, lon: -119.714000 }
        };
    }

    if (typeof storedCoordinates === 'object') {
        for (const [pointKey, coords] of Object.entries(storedCoordinates)) {
            const latInput = document.getElementById(`${pointKey}-lat`);
            const lonInput = document.getElementById(`${pointKey}-lon`);
            if (latInput && lonInput) {
                latInput.value = coords.lat;
                lonInput.value = coords.lon;
            }
        }
    }
    document.getElementById('triangulationForm').addEventListener('submit', formSubmit);
});

function formSubmit(e) {
    e.preventDefault();

    // Собираем данные формы универсально с помощью FormData
    const form = document.getElementById('triangulationForm');
    const formData = new FormData(form);
    const data = {};
    formData.forEach((value, key) => {
        data[key] = value;
    });

    fetch('controller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
        .then(response => response.json())
        .then(result => {
            const resultEl = document.getElementById('result');
            if (result.success) {
                resultEl.innerText = `Координаты: ${result.latitude}, ${result.longitude}`;
            } else {
                resultEl.innerText = `Ошибка: ${result.error}`;
            }
            resultEl.style.display = 'block';
        })
        .catch(error => {
            console.error('Ошибка:', error);
        });
}
