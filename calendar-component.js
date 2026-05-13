class HospitalCalendar {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        this.events = [];
        this.currentDate = new Date(); // Start at today
        this.startHour = options.startHour || 8; // 08:00
        this.endHour = options.endHour || 20; // 20:00
        this.onSlotClick = options.onSlotClick || function(){};
        
        this.initStyles();
        this.render();
    }

    initStyles() {
        if(document.getElementById('hospital-calendar-styles')) return;
        const style = document.createElement('style');
        style.id = 'hospital-calendar-styles';
        style.innerHTML = `
            .hc-wrapper { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; flex-direction: column; height: 600px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; background: white; }
            .hc-header { display: flex; justify-content: space-between; align-items: center; padding: 15px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
            .hc-header h3 { margin: 0; color: #1e293b; }
            .hc-btn { background: #3b82f6; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-weight: bold; }
            .hc-btn:hover { background: #2563eb; }
            
            .hc-body { display: flex; flex: 1; overflow-y: auto; }
            .hc-time-col { width: 60px; flex-shrink: 0; background: #f8fafc; border-right: 1px solid #e2e8f0; }
            .hc-time-slot { height: 60px; border-bottom: 1px solid #e2e8f0; text-align: right; padding-right: 5px; font-size: 12px; color: #64748b; box-sizing: border-box; }
            
            .hc-grid { display: flex; flex: 1; }
            .hc-day-col { flex: 1; border-right: 1px solid #e2e8f0; position: relative; min-width: 100px; }
            .hc-day-col:last-child { border-right: none; }
            
            .hc-day-header { text-align: center; padding: 10px 0; background: #f1f5f9; border-bottom: 1px solid #e2e8f0; font-weight: 600; font-size: 14px; color: #334155; position: sticky; top: 0; z-index: 10; }
            .hc-day-body { position: relative; height: ${(this.endHour - this.startHour) * 60}px; background-size: 100% 60px; background-image: linear-gradient(to bottom, #f1f5f9 1px, transparent 1px); cursor: pointer; }
            .hc-day-body:hover { background-color: #f8fafc; }
            
            .hc-event { position: absolute; left: 2%; width: 96%; background: #0ea5e9; color: white; border-radius: 4px; padding: 4px; font-size: 11px; box-sizing: border-box; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #0284c7; }
            .hc-event.reserved { background: #f59e0b; border-left-color: #d97706; }
            .hc-event.in_use { background: #ef4444; border-left-color: #b91c1c; }
        `;
        document.head.appendChild(style);
    }

    getStartOfWeek(date) {
        const d = new Date(date);
        d.setHours(0, 0, 0, 0);
        const day = d.getDay(), diff = d.getDate() - day + (day === 0 ? -6 : 1); 
        return new Date(d.setDate(diff));
    }

    formatDate(d) {
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    prevWeek() {
        this.currentDate.setDate(this.currentDate.getDate() - 7);
        this.render();
    }

    nextWeek() {
        this.currentDate.setDate(this.currentDate.getDate() + 7);
        this.render();
    }

    setEvents(eventsData) {
        // eventsData: [{ id, start_time: "YYYY-MM-DD HH:MM:SS", end_time: "YYYY-MM-DD HH:MM:SS", title: "Dr. X" }]
        this.events = eventsData;
        this.render();
    }

    render() {
        if(!this.container) return;
        
        const startOfWeek = this.getStartOfWeek(this.currentDate);
        const endOfWeek = new Date(startOfWeek); endOfWeek.setDate(startOfWeek.getDate() + 6);
        
        const monthNames = ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"];
        const dayNames = ["Lun", "Mar", "Mer", "Jeu", "Ven", "Sam", "Dim"];

        let html = `
            <div class="hc-wrapper">
                <div class="hc-header">
                    <button class="hc-btn" id="hc-prev">&larr; Précédent</button>
                    <h3>Semaine du ${startOfWeek.getDate()} ${monthNames[startOfWeek.getMonth()]} au ${endOfWeek.getDate()} ${monthNames[endOfWeek.getMonth()]}</h3>
                    <button class="hc-btn" id="hc-next">Suivant &rarr;</button>
                </div>
                <div class="hc-body">
                    <div class="hc-time-col">
                        <div style="height:41px; border-bottom:1px solid #e2e8f0; position:sticky; top:0; background:#f8fafc; z-index:10;"></div>
        `;

        for(let i = this.startHour; i < this.endHour; i++) {
            html += `<div class="hc-time-slot">${i}:00</div>`;
        }

        html += `</div><div class="hc-grid">`;

        // Parse local date safely to avoid UTC offset bugs
        const parseLocal = (dateString) => {
            if(!dateString) return new Date();
            const str = dateString.replace('T', ' ');
            const parts = str.split(/[- :]/);
            if(parts.length < 5) {
                // fallback for pure dates "YYYY-MM-DD"
                if(parts.length === 3) return new Date(parts[0], parts[1]-1, parts[2], 0, 0, 0);
                return new Date(dateString);
            }
            return new Date(parts[0], parts[1]-1, parts[2], parts[3], parts[4], parts[5]||0);
        };

        // Render Days
        for(let i = 0; i < 7; i++) {
            const currentDay = new Date(startOfWeek.getFullYear(), startOfWeek.getMonth(), startOfWeek.getDate() + i);
            const dateStr = this.formatDate(currentDay);

            html += `
                <div class="hc-day-col">
                    <div class="hc-day-header">${dayNames[i]} ${currentDay.getDate()}</div>
                    <div class="hc-day-body" data-date="${dateStr}">
            `;

            // Check all events to see if they overlap with this day's visible grid
            const gridStart = new Date(currentDay.getFullYear(), currentDay.getMonth(), currentDay.getDate(), this.startHour, 0, 0);
            const gridEnd = new Date(currentDay.getFullYear(), currentDay.getMonth(), currentDay.getDate(), this.endHour, 0, 0);

            this.events.forEach(e => {
                const sDate = parseLocal(e.start_time);
                const eDate = parseLocal(e.end_time);
                
                // Does it overlap with the grid time of this specific day?
                if (sDate < gridEnd && eDate > gridStart) {
                    const renderStart = new Date(Math.max(sDate.getTime(), gridStart.getTime()));
                    const renderEnd = new Date(Math.min(eDate.getTime(), gridEnd.getTime()));

                    const startMins = (renderStart.getTime() - gridStart.getTime()) / 60000;
                    const durationMins = (renderEnd.getTime() - renderStart.getTime()) / 60000;
                    
                    if (durationMins > 0) {
                        let className = "hc-event";
                        if(e.status === 'reserved' || e.title === 'Réservation Matériel' || e.used_by === 'Réservation Matériel') className += ' reserved';
                        if(e.status === 'in_use') className += ' in_use';

                        // Display original times in the box
                        const origStart = `${String(sDate.getHours()).padStart(2, '0')}:${String(sDate.getMinutes()).padStart(2, '0')}`;
                        const origEnd = `${String(eDate.getHours()).padStart(2, '0')}:${String(eDate.getMinutes()).padStart(2, '0')}`;
                        const titleText = e.used_by || e.title || e.patient_name || 'Occupé';

                        html += `
                            <div class="${className}" style="top: ${startMins}px; height: ${durationMins}px;" title="${titleText} (${e.start_time} - ${e.end_time})">
                                <strong>${titleText}</strong><br>
                                ${origStart} - ${origEnd}
                            </div>
                        `;
                    }
                }
            });

            html += `</div></div>`;
        }

        html += `</div></div></div>`;
        this.container.innerHTML = html;

        // Attach listeners
        this.container.querySelector('#hc-prev').addEventListener('click', () => this.prevWeek());
        this.container.querySelector('#hc-next').addEventListener('click', () => this.nextWeek());
        
        const bodies = this.container.querySelectorAll('.hc-day-body');
        bodies.forEach(b => {
            b.addEventListener('click', (e) => {
                if(e.target.classList.contains('hc-event')) return; // Ignore event clicks
                const rect = b.getBoundingClientRect();
                const y = e.clientY - rect.top;
                const totalMinutes = y + (this.startHour * 60);
                
                const hour = Math.floor(totalMinutes / 60);
                const min = Math.floor(totalMinutes % 60);
                // snap to 15 mins
                const snappedMin = Math.round(min / 15) * 15;
                
                const date = b.getAttribute('data-date');
                const timeStr = `${String(hour).padStart(2, '0')}:${String(snappedMin).padStart(2, '0')}`;
                
                this.onSlotClick(date, timeStr);
            });
        });
    }
}
