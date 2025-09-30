<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rate Checker</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; }
        form { display: grid; gap: 10px; }
        button { padding: 10px; background: #007bff; color: white; border: none; cursor: pointer; }
        #response { margin-top: 20px; border: 1px solid #ccc; padding: 10px; white-space: pre-wrap; }
    </style>
</head>
<body>
    <h1>Check Rates and Availability</h1>
    <form id="rateForm">
        <label>Unit Name:
            <select name="unitName">
                <option>Namib Desert Lodge</option>
                <option>Etosha Safari Lodge</option>
            </select>
        </label>
        <label>Arrival (dd/mm/yyyy): <input type="text" name="arrival" placeholder="01/10/2025" required></label>
        <label>Departure (dd/mm/yyyy): <input type="text" name="departure" placeholder="05/10/2025" required></label>
        <label>Occupants: <input type="number" name="occupants" min="1" required></label>
        <label>Ages (comma-separated): <input type="text" name="ages" placeholder="25,10" required></label>
        <button type="submit">Get Rates</button>
    </form>
    <div id="response"></div>

    <script>
        document.getElementById('rateForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = {
                "Unit Name": formData.get('unitName'),
                "Arrival": formData.get('arrival'),
                "Departure": formData.get('departure'),
                "Occupants": parseInt(formData.get('occupants')),
                "Ages": formData.get('ages').split(',').map(a => parseInt(a.trim()))
            };

            try {
                const res = await fetch('../backend/api.php', { // Adjust path if needed in Codespaces
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const json = await res.json();
                const display = document.getElementById('response');
                if (json.error) {
                    display.textContent = `Error: ${json.error}`;
                } else {
                    // Display key data (fallback to full JSON if fields missing)
                    const unit = json['Unit Name'] || 'N/A';
                    const range = json['Date Range'] || 'N/A';
                    const rate = json.rate || json.totalRate || 'N/A'; // Assume possible fields
                    const avail = json.availability || json.available || 'N/A';
                    display.innerHTML = `
                        <strong>Unit Name:</strong> ${unit}<br>
                        <strong>Date Range:</strong> ${range}<br>
                        <strong>Rate:</strong> ${rate}<br>
                        <strong>Availability:</strong> ${avail}<br>
                        <strong>Full Response:</strong><pre>${JSON.stringify(json, null, 2)}</pre>
                    `;
                }
            } catch (err) {
                document.getElementById('response').textContent = `Error: ${err.message}`;
            }
        });
    </script>
</body>
</html>