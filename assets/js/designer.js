/**
 * Database Designer JavaScript
 * Professional visual database diagram tool
 */

class DatabaseDesignerApp {
    constructor() {
        this.canvas = document.getElementById('designer-canvas');
        this.ctx = this.canvas.getContext('2d');
        this.tables = [];
        this.relationships = [];
        this.selectedTable = null;
        this.draggedTable = null;
        this.dragOffset = { x: 0, y: 0 };
        this.zoom = 1;
        this.panOffset = { x: 0, y: 0 };
        this.isPanning = false;
        this.panStart = { x: 0, y: 0 };
        this.database = window.database;
        this.showGrid = true;
        this.isCreatingRelationship = false;
        this.relationshipStart = null;
        this.relationshipEnd = null;
        this.hoveredColumn = null;
        this.minimap = { show: false, x: 0, y: 0, width: 200, height: 150 };
        
        this.init();
    }
    
    init() {
        this.setupCanvas();
        this.setupEventListeners();
        this.loadSchema();
    }
    
    setupCanvas() {
        // Set canvas size
        this.canvas.width = this.canvas.offsetWidth;
        this.canvas.height = this.canvas.offsetHeight;
        
        // Handle resize
        window.addEventListener('resize', () => {
            this.canvas.width = this.canvas.offsetWidth;
            this.canvas.height = this.canvas.offsetHeight;
            this.draw();
        });
    }
    
    setupEventListeners() {
        // Mouse events for dragging
        this.canvas.addEventListener('mousedown', (e) => this.handleMouseDown(e));
        this.canvas.addEventListener('mousemove', (e) => this.handleMouseMove(e));
        this.canvas.addEventListener('mouseup', (e) => this.handleMouseUp(e));
        this.canvas.addEventListener('dblclick', (e) => this.handleDoubleClick(e));
        
        // Mouse wheel for zoom
        this.canvas.addEventListener('wheel', (e) => this.handleWheel(e));
        
        // Context menu
        this.canvas.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            this.handleRightClick(e);
        });
    }
    
    async loadSchema() {
        try {
            const params = {
                action: 'get_schema'
            };
            
            // Check if in file mode or database mode
            if (window.designerMode === 'file' && window.designerFile) {
                params.file = window.designerFile;
                this.mode = 'file';
                this.filename = window.designerFile;
            } else {
                params.database = this.database;
                this.mode = 'database';
            }
            
            const response = await fetch('handlers/designer_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(params)
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Store mode information
                this.dataMode = data.mode;
                
                // Update display name if in file mode
                if (data.mode === 'file' && data.metadata) {
                    const nameElement = document.getElementById('designer-name');
                    if (nameElement) {
                        nameElement.textContent = data.metadata.name;
                        if (data.metadata.description) {
                            nameElement.title = data.metadata.description;
                        }
                    }
                }
                
                this.tables = data.schema.tables.map((table, index) => {
                    // Auto-position if no position saved
                    const position = data.positions[table.name] || this.calculateAutoPosition(index);
                    
                    return {
                        name: table.name,
                        columns: table.columns,
                        indexes: table.indexes || [],
                        rowCount: table.rowCount || 0,
                        comment: table.comment || '',
                        x: position.x,
                        y: position.y,
                        width: 250,
                        height: this.calculateTableHeight(table.columns)
                    };
                });
                
                this.relationships = data.schema.relationships;
                
                // Update table count
                document.getElementById('table-count').textContent = this.tables.length;
                
                // Update tables list in sidebar
                if (typeof updateTablesList === 'function') {
                    updateTablesList();
                }
                
                // Hide loading overlay
                const loadingOverlay = document.getElementById('loading-overlay');
                if (loadingOverlay) {
                    loadingOverlay.style.display = 'none';
                }
                
                this.draw();
            }
        } catch (error) {
            console.error('Error loading schema:', error);
            showToast('Error loading database schema', 'error');
            
            // Hide loading overlay on error too
            const loadingOverlay = document.getElementById('loading-overlay');
            if (loadingOverlay) {
                loadingOverlay.style.display = 'none';
            }
        }
    }
    
    calculateAutoPosition(index) {
        const cols = 4;
        const spacing = 300;
        const col = index % cols;
        const row = Math.floor(index / cols);
        
        return {
            x: 50 + (col * spacing),
            y: 50 + (row * spacing)
        };
    }
    
    calculateTableHeight(columns) {
        const headerHeight = 40;
        const rowHeight = 25;
        const padding = 10;
        return headerHeight + (columns.length * rowHeight) + padding;
    }
    
    draw() {
        // Clear canvas
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        
        // Save context state
        this.ctx.save();
        
        // Apply zoom and pan
        this.ctx.translate(this.panOffset.x, this.panOffset.y);
        this.ctx.scale(this.zoom, this.zoom);
        
        // Draw grid if enabled
        if (this.showGrid) {
            this.drawGrid();
        }
        
        // Draw relationships first (behind tables)
        this.drawRelationships();
        
        // Draw relationship preview if creating
        if (this.isCreatingRelationship && this.relationshipStart && this.relationshipEnd) {
            this.drawRelationshipPreview();
        }
        
        // Draw tables
        this.tables.forEach(table => this.drawTable(table));
        
        // Draw minimap
        if (this.minimap.show) {
            this.drawMinimap();
        }
        
        // Restore context
        this.ctx.restore();
    }
    
    drawGrid() {
        const gridSize = 20;
        this.ctx.strokeStyle = '#f0f0f0';
        this.ctx.lineWidth = 1;
        
        // Vertical lines
        for (let x = 0; x < this.canvas.width / this.zoom; x += gridSize) {
            this.ctx.beginPath();
            this.ctx.moveTo(x, 0);
            this.ctx.lineTo(x, this.canvas.height / this.zoom);
            this.ctx.stroke();
        }
        
        // Horizontal lines
        for (let y = 0; y < this.canvas.height / this.zoom; y += gridSize) {
            this.ctx.beginPath();
            this.ctx.moveTo(0, y);
            this.ctx.lineTo(this.canvas.width / this.zoom, y);
            this.ctx.stroke();
        }
    }
    
    drawTable(table) {
        const isSelected = this.selectedTable === table;
        
        // Shadow
        if (isSelected) {
            this.ctx.shadowColor = '#3b82f6';
            this.ctx.shadowBlur = 15;
        } else {
            this.ctx.shadowColor = 'rgba(0,0,0,0.1)';
            this.ctx.shadowBlur = 5;
        }
        
        // Table container
        this.ctx.fillStyle = '#ffffff';
        this.ctx.strokeStyle = isSelected ? '#3b82f6' : '#e5e7eb';
        this.ctx.lineWidth = isSelected ? 3 : 1;
        this.roundRect(table.x, table.y, table.width, table.height, 8);
        this.ctx.fill();
        this.ctx.stroke();
        
        // Reset shadow
        this.ctx.shadowBlur = 0;
        
        // Header
        this.ctx.fillStyle = isSelected ? '#3b82f6' : '#1f2937';
        this.roundRect(table.x, table.y, table.width, 40, 8, true, false);
        this.ctx.fill();
        
        // Table name
        this.ctx.fillStyle = '#ffffff';
        this.ctx.font = 'bold 14px Inter, system-ui, sans-serif';
        this.ctx.textAlign = 'left';
        this.ctx.fillText(table.name, table.x + 12, table.y + 25);
        
        // Row count badge
        this.ctx.fillStyle = 'rgba(255,255,255,0.2)';
        const badgeText = `${table.rowCount} rows`;
        const badgeWidth = this.ctx.measureText(badgeText).width + 16;
        this.roundRect(table.x + table.width - badgeWidth - 8, table.y + 10, badgeWidth, 20, 10);
        this.ctx.fill();
        
        this.ctx.fillStyle = '#ffffff';
        this.ctx.font = '11px Inter, system-ui, sans-serif';
        this.ctx.textAlign = 'center';
        this.ctx.fillText(badgeText, table.x + table.width - badgeWidth/2 - 8, table.y + 24);
        
        // Columns
        let yOffset = table.y + 50;
        this.ctx.font = '12px "Fira Code", "Courier New", monospace';
        this.ctx.textAlign = 'left';
        
        table.columns.forEach((col, index) => {
            // Column background on hover
            const isHovered = this.hoveredColumn && 
                            this.hoveredColumn.table === table && 
                            this.hoveredColumn.columnIndex === index;
            
            if (isHovered) {
                this.ctx.fillStyle = 'rgba(59, 130, 246, 0.1)';
                this.ctx.fillRect(table.x, yOffset, table.width, 25);
            }
            
            // Connection point indicator for relationship mode
            if (this.isCreatingRelationship) {
                this.ctx.fillStyle = isHovered ? '#3b82f6' : '#e5e7eb';
                this.ctx.beginPath();
                this.ctx.arc(table.x + table.width - 6, yOffset + 12, 4, 0, Math.PI * 2);
                this.ctx.fill();
            }
            
            // Key icon
            if (col.key === 'PRI') {
                this.ctx.fillStyle = '#fbbf24';
                this.ctx.font = 'bold 12px "Font Awesome 6 Free"';
                this.ctx.fillText('\uf084', table.x + 12, yOffset + 15); // key icon
            } else if (col.key === 'UNI') {
                this.ctx.fillStyle = '#8b5cf6';
                this.ctx.font = 'bold 12px "Font Awesome 6 Free"';
                this.ctx.fillText('\uf005', table.x + 12, yOffset + 15); // star icon
            }
            
            // Column name
            this.ctx.fillStyle = isHovered ? '#1e40af' : '#1f2937';
            this.ctx.font = col.key === 'PRI' ? 'bold 12px "Fira Code", monospace' : '12px "Fira Code", monospace';
            this.ctx.fillText(col.name, table.x + 32, yOffset + 15);
            
            // Column type
            this.ctx.fillStyle = isHovered ? '#3b82f6' : '#6b7280';
            this.ctx.font = '11px "Fira Code", monospace';
            this.ctx.textAlign = 'right';
            this.ctx.fillText(col.type, table.x + table.width - 20, yOffset + 15);
            this.ctx.textAlign = 'left';
            
            yOffset += 25;
        });
    }
    
    drawRelationships() {
        this.relationships.forEach(rel => {
            const fromTable = this.tables.find(t => t.name === rel.table);
            const toTable = this.tables.find(t => t.name === rel.referenced_table);
            
            if (!fromTable || !toTable) return;
            
            // Find column positions
            const fromCol = fromTable.columns.findIndex(c => c.name === rel.column);
            const toCol = toTable.columns.findIndex(c => c.name === rel.referenced_column);
            
            if (fromCol === -1 || toCol === -1) return;
            
            // Calculate connection points
            const fromY = fromTable.y + 50 + (fromCol * 25) + 12;
            const toY = toTable.y + 50 + (toCol * 25) + 12;
            
            // Determine which side to connect from
            const fromX = fromTable.x + fromTable.width;
            const toX = toTable.x;
            
            // Draw the line
            this.drawRelationshipLine(fromX, fromY, toX, toY, rel.on_delete);
        });
    }
    
    drawRelationshipLine(x1, y1, x2, y2, onDelete) {
        // Line color based on relationship type
        let color = '#94a3b8';
        if (onDelete === 'CASCADE') color = '#ef4444';
        else if (onDelete === 'SET NULL') color = '#f59e0b';
        
        this.ctx.strokeStyle = color;
        this.ctx.lineWidth = 2;
        
        // Draw bezier curve
        this.ctx.beginPath();
        this.ctx.moveTo(x1, y1);
        
        const midX = (x1 + x2) / 2;
        this.ctx.bezierCurveTo(midX, y1, midX, y2, x2, y2);
        this.ctx.stroke();
        
        // Draw crow's foot notation at end
        this.drawCrowsFoot(x2, y2, onDelete);
        
        // Draw circle at start (one side)
        this.ctx.fillStyle = color;
        this.ctx.beginPath();
        this.ctx.arc(x1, y1, 4, 0, Math.PI * 2);
        this.ctx.fill();
    }
    
    drawCrowsFoot(x, y, type) {
        const size = 12;
        
        this.ctx.save();
        this.ctx.translate(x, y);
        
        // Draw crow's foot (many side)
        this.ctx.beginPath();
        this.ctx.moveTo(-size, -size/2);
        this.ctx.lineTo(0, 0);
        this.ctx.lineTo(-size, size/2);
        this.ctx.stroke();
        
        // Draw circle for optional
        if (type === 'SET NULL') {
            this.ctx.beginPath();
            this.ctx.arc(-size - 5, 0, 3, 0, Math.PI * 2);
            this.ctx.stroke();
        }
        
        this.ctx.restore();
    }
    
    roundRect(x, y, width, height, radius, fillTop = true, fillBottom = true) {
        this.ctx.beginPath();
        
        if (fillTop) {
            this.ctx.moveTo(x + radius, y);
            this.ctx.lineTo(x + width - radius, y);
            this.ctx.quadraticCurveTo(x + width, y, x + width, y + radius);
        } else {
            this.ctx.moveTo(x + width, y);
        }
        
        if (fillBottom) {
            this.ctx.lineTo(x + width, y + height - radius);
            this.ctx.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
            this.ctx.lineTo(x + radius, y + height);
            this.ctx.quadraticCurveTo(x, y + height, x, y + height - radius);
        } else {
            this.ctx.lineTo(x + width, y + height);
            this.ctx.lineTo(x, y + height);
        }
        
        this.ctx.lineTo(x, y + radius);
        this.ctx.quadraticCurveTo(x, y, x + radius, y);
        this.ctx.closePath();
    }
    
    getMousePos(event) {
        const rect = this.canvas.getBoundingClientRect();
        const x = (event.clientX - rect.left - this.panOffset.x) / this.zoom;
        const y = (event.clientY - rect.top - this.panOffset.y) / this.zoom;
        return { x, y };
    }
    
    getTableAtPosition(x, y) {
        // Check in reverse order (top tables first)
        for (let i = this.tables.length - 1; i >= 0; i--) {
            const table = this.tables[i];
            if (x >= table.x && x <= table.x + table.width &&
                y >= table.y && y <= table.y + table.height) {
                return table;
            }
        }
        return null;
    }
    
    getColumnAtPosition(x, y) {
        const table = this.getTableAtPosition(x, y);
        if (!table) return null;
        
        // Check if in column area (below header)
        if (y < table.y + 50) return null;
        
        const relativeY = y - table.y - 50;
        const columnIndex = Math.floor(relativeY / 25);
        
        if (columnIndex >= 0 && columnIndex < table.columns.length) {
            const column = table.columns[columnIndex];
            return {
                table: table,
                column: column,
                columnIndex: columnIndex,
                x: table.x + table.width,
                y: table.y + 50 + (columnIndex * 25) + 12
            };
        }
        
        return null;
    }
    
    handleMouseDown(event) {
        const pos = this.getMousePos(event);
        
        // Middle mouse button or Shift key for panning
        if (event.shiftKey || event.button === 1) {
            this.isPanning = true;
            this.panStart = { x: event.clientX - this.panOffset.x, y: event.clientY - this.panOffset.y };
            this.canvas.style.cursor = 'grabbing';
            return;
        }
        
        // Check if clicking on a column for relationship creation
        if (this.isCreatingRelationship) {
            const columnInfo = this.getColumnAtPosition(pos.x, pos.y);
            if (columnInfo) {
                if (!this.relationshipStart) {
                    this.relationshipStart = columnInfo;
                    showToast('Select target column', 'info');
                } else {
                    this.relationshipEnd = columnInfo;
                    this.openRelationshipModalQuick();
                }
                return;
            }
        }
        
        const table = this.getTableAtPosition(pos.x, pos.y);
        
        if (table) {
            this.draggedTable = table;
            this.selectedTable = table;
            this.dragOffset = {
                x: pos.x - table.x,
                y: pos.y - table.y
            };
            this.canvas.style.cursor = 'move';
            this.draw();
        } else {
            // If clicking on empty canvas, enable panning
            this.selectedTable = null;
            this.isPanning = true;
            this.panStart = { x: event.clientX - this.panOffset.x, y: event.clientY - this.panOffset.y };
            this.canvas.style.cursor = 'grab';
            this.draw();
        }
    }
    
    handleMouseMove(event) {
        const pos = this.getMousePos(event);
        
        if (this.isPanning) {
            this.panOffset.x = event.clientX - this.panStart.x;
            this.panOffset.y = event.clientY - this.panStart.y;
            this.draw();
            return;
        }
        
        if (this.draggedTable) {
            this.draggedTable.x = pos.x - this.dragOffset.x;
            this.draggedTable.y = pos.y - this.dragOffset.y;
            this.draw();
            return;
        }
        
        // Handle relationship creation preview
        if (this.isCreatingRelationship && this.relationshipStart) {
            this.relationshipEnd = { x: pos.x, y: pos.y, isPreview: true };
            this.draw();
            return;
        }
        
        // Update hover state for columns
        const columnInfo = this.getColumnAtPosition(pos.x, pos.y);
        if (columnInfo !== this.hoveredColumn) {
            this.hoveredColumn = columnInfo;
            this.draw();
        }
        
        // Update cursor
        const table = this.getTableAtPosition(pos.x, pos.y);
        if (this.isCreatingRelationship) {
            this.canvas.style.cursor = columnInfo ? 'crosshair' : 'not-allowed';
        } else {
            this.canvas.style.cursor = table ? 'pointer' : 'grab';
        }
    }
    
    handleMouseUp(event) {
        if (this.isPanning) {
            this.isPanning = false;
            this.canvas.style.cursor = 'grab';
        }
        
        if (this.draggedTable) {
            // Save position
            this.saveTablePosition(this.draggedTable);
            this.draggedTable = null;
            this.canvas.style.cursor = 'grab';
        }
    }
    
    handleDoubleClick(event) {
        const pos = this.getMousePos(event);
        const table = this.getTableAtPosition(pos.x, pos.y);
        
        if (table) {
            this.openTableEditor(table);
        }
    }
    
    handleRightClick(event) {
        const pos = this.getMousePos(event);
        const table = this.getTableAtPosition(pos.x, pos.y);
        
        if (table) {
            this.showTableContextMenu(table, event);
        } else {
            this.showCanvasContextMenu(event);
        }
    }
    
    handleWheel(event) {
        event.preventDefault();
        
        const delta = event.deltaY > 0 ? 0.9 : 1.1;
        const newZoom = this.zoom * delta;
        
        if (newZoom >= 0.1 && newZoom <= 3) {
            const mouseX = event.clientX - this.canvas.offsetLeft;
            const mouseY = event.clientY - this.canvas.offsetTop;
            
            this.panOffset.x = mouseX - (mouseX - this.panOffset.x) * delta;
            this.panOffset.y = mouseY - (mouseY - this.panOffset.y) * delta;
            
            this.zoom = newZoom;
            this.draw();
            
            // Update zoom display
            document.getElementById('zoom-level').textContent = Math.round(this.zoom * 100) + '%';
        }
    }
    
    async saveTablePosition(table) {
        try {
            await fetch('handlers/designer_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'save_position',
                    database: this.database,
                    table: table.name,
                    x: table.x,
                    y: table.y
                })
            });
        } catch (error) {
            console.error('Error saving position:', error);
        }
    }
    
    // UI Methods
    zoomIn() {
        this.zoom = Math.min(3, this.zoom * 1.2);
        this.draw();
        document.getElementById('zoom-level').textContent = Math.round(this.zoom * 100) + '%';
    }
    
    zoomOut() {
        this.zoom = Math.max(0.1, this.zoom / 1.2);
        this.draw();
        document.getElementById('zoom-level').textContent = Math.round(this.zoom * 100) + '%';
    }
    
    resetZoom() {
        this.zoom = 1;
        this.panOffset = { x: 0, y: 0 };
        this.draw();
        document.getElementById('zoom-level').textContent = '100%';
    }
    
    autoLayout() {
        this.tables.forEach((table, index) => {
            const position = this.calculateAutoPosition(index);
            table.x = position.x;
            table.y = position.y;
            this.saveTablePosition(table);
        });
        this.draw();
        showToast('Tables rearranged automatically');
    }
    
    toggleGrid() {
        this.showGrid = !this.showGrid;
        this.draw();
        showToast(this.showGrid ? 'Grid enabled' : 'Grid disabled', 'info');
    }
    
    drawRelationshipPreview() {
        if (!this.relationshipStart) return;
        
        const startX = this.relationshipStart.x;
        const startY = this.relationshipStart.y;
        
        let endX, endY;
        if (this.relationshipEnd.isPreview) {
            endX = this.relationshipEnd.x;
            endY = this.relationshipEnd.y;
        } else {
            endX = this.relationshipEnd.x;
            endY = this.relationshipEnd.y;
        }
        
        // Draw dashed preview line
        this.ctx.strokeStyle = '#3b82f6';
        this.ctx.lineWidth = 2;
        this.ctx.setLineDash([5, 5]);
        
        this.ctx.beginPath();
        this.ctx.moveTo(startX, startY);
        
        const midX = (startX + endX) / 2;
        this.ctx.bezierCurveTo(midX, startY, midX, endY, endX, endY);
        this.ctx.stroke();
        
        // Reset line dash
        this.ctx.setLineDash([]);
        
        // Draw start point
        this.ctx.fillStyle = '#3b82f6';
        this.ctx.beginPath();
        this.ctx.arc(startX, startY, 6, 0, Math.PI * 2);
        this.ctx.fill();
        
        // Draw end point if hovering column
        if (!this.relationshipEnd.isPreview) {
            this.ctx.beginPath();
            this.ctx.arc(endX, endY, 6, 0, Math.PI * 2);
            this.ctx.fill();
        }
    }
    
    drawMinimap() {
        // Calculate minimap position (bottom-right corner)
        const margin = 20;
        const mmX = (this.canvas.width / this.zoom) - this.minimap.width - margin;
        const mmY = (this.canvas.height / this.zoom) - this.minimap.height - margin;
        
        // Draw minimap background
        this.ctx.fillStyle = 'rgba(255, 255, 255, 0.9)';
        this.ctx.strokeStyle = '#3b82f6';
        this.ctx.lineWidth = 2;
        this.roundRect(mmX, mmY, this.minimap.width, this.minimap.height, 8);
        this.ctx.fill();
        this.ctx.stroke();
        
        // Calculate scale for minimap
        if (this.tables.length === 0) return;
        
        let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
        this.tables.forEach(table => {
            minX = Math.min(minX, table.x);
            minY = Math.min(minY, table.y);
            maxX = Math.max(maxX, table.x + table.width);
            maxY = Math.max(maxY, table.y + table.height);
        });
        
        const contentWidth = maxX - minX;
        const contentHeight = maxY - minY;
        const scale = Math.min(
            (this.minimap.width - 20) / contentWidth,
            (this.minimap.height - 20) / contentHeight
        );
        
        // Draw tables on minimap
        this.tables.forEach(table => {
            const x = mmX + 10 + (table.x - minX) * scale;
            const y = mmY + 10 + (table.y - minY) * scale;
            const w = table.width * scale;
            const h = table.height * scale;
            
            this.ctx.fillStyle = table === this.selectedTable ? '#3b82f6' : '#cbd5e1';
            this.ctx.fillRect(x, y, w, h);
        });
        
        // Draw viewport indicator
        const vpX = mmX + 10 + (-this.panOffset.x / this.zoom - minX) * scale;
        const vpY = mmY + 10 + (-this.panOffset.y / this.zoom - minY) * scale;
        const vpW = (this.canvas.width / this.zoom) * scale;
        const vpH = (this.canvas.height / this.zoom) * scale;
        
        this.ctx.strokeStyle = '#ef4444';
        this.ctx.lineWidth = 2;
        this.ctx.strokeRect(vpX, vpY, vpW, vpH);
    }
    
    toggleMinimap() {
        this.minimap.show = !this.minimap.show;
        this.draw();
        showToast(this.minimap.show ? 'Minimap enabled' : 'Minimap disabled', 'info');
    }
    
    toggleRelationshipMode() {
        this.isCreatingRelationship = !this.isCreatingRelationship;
        this.relationshipStart = null;
        this.relationshipEnd = null;
        
        if (this.isCreatingRelationship) {
            showToast('Relationship Mode: Click on source column', 'info');
            this.canvas.style.cursor = 'crosshair';
        } else {
            showToast('Relationship mode disabled', 'info');
            this.canvas.style.cursor = 'default';
        }
        
        this.draw();
    }
    
    openRelationshipModalQuick() {
        if (!this.relationshipStart || !this.relationshipEnd || this.relationshipEnd.isPreview) {
            return;
        }
        
        window.currentEditTable = this.relationshipStart.table.name;
        
        // Pre-fill the relationship modal
        document.getElementById('rel-from-table').textContent = this.relationshipStart.table.name;
        document.getElementById('rel-from-column').innerHTML = 
            `<option value="${this.relationshipStart.column.name}" selected>${this.relationshipStart.column.name}</option>`;
        
        // Populate target table options
        const refSelect = document.getElementById('rel-to-table');
        refSelect.innerHTML = this.tables
            .map(t => `<option value="${t.name}" ${t.name === this.relationshipEnd.table.name ? 'selected' : ''}>${t.name}</option>`)
            .join('');
        
        // Populate target column options
        const refColSelect = document.getElementById('rel-to-column');
        refColSelect.innerHTML = this.relationshipEnd.table.columns
            .map(c => `<option value="${c.name}" ${c.name === this.relationshipEnd.column.name ? 'selected' : ''}>${c.name}</option>`)
            .join('');
        
        document.getElementById('addRelationshipModal').classList.remove('hidden');
        document.getElementById('addRelationshipModal').classList.add('flex');
        
        // Reset relationship mode
        this.isCreatingRelationship = false;
        this.relationshipStart = null;
        this.relationshipEnd = null;
        this.canvas.style.cursor = 'default';
        this.draw();
    }
    
    exportAsImage() {
        // Create a temporary canvas with white background
        const tempCanvas = document.createElement('canvas');
        tempCanvas.width = this.canvas.width;
        tempCanvas.height = this.canvas.height;
        const tempCtx = tempCanvas.getContext('2d');
        
        // Fill white background
        tempCtx.fillStyle = '#ffffff';
        tempCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);
        
        // Draw the diagram
        tempCtx.drawImage(this.canvas, 0, 0);
        
        // Download
        const link = document.createElement('a');
        link.download = `${this.database}_diagram.png`;
        link.href = tempCanvas.toDataURL();
        link.click();
        
        showToast('Diagram exported as image');
    }
    
    openTableEditor(table) {
        // Open table editor modal
        document.getElementById('table-editor-name').value = table.name;
        document.getElementById('table-editor-comment').value = table.comment;
        
        // Populate columns
        const columnsContainer = document.getElementById('table-editor-columns');
        columnsContainer.innerHTML = '';
        
        table.columns.forEach(col => {
            columnsContainer.innerHTML += `
                <div class="flex items-center gap-2 p-2 border rounded">
                    <input type="text" value="${col.name}" class="flex-1 px-2 py-1 border rounded">
                    <input type="text" value="${col.type}" class="w-32 px-2 py-1 border rounded">
                    <button class="text-red-600 hover:text-red-800">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
        });
        
        document.getElementById('tableEditorModal').classList.remove('hidden');
        document.getElementById('tableEditorModal').classList.add('flex');
    }
    
    showTableContextMenu(table, event) {
        // Remove any existing context menu
        const existingMenu = document.getElementById('context-menu');
        if (existingMenu) existingMenu.remove();
        
        // Create context menu
        const menu = document.createElement('div');
        menu.id = 'context-menu';
        menu.className = 'fixed bg-white rounded-lg shadow-xl border border-gray-200 py-2 z-50';
        menu.style.left = event.clientX + 'px';
        menu.style.top = event.clientY + 'px';
        
        menu.innerHTML = `
            <button class="w-full px-4 py-2 text-left hover:bg-gray-100 flex items-center gap-2" onclick="designer.editTable('${table.name}')">
                <i class="fas fa-edit text-blue-600 w-4"></i>
                <span>Edit Table</span>
            </button>
            <button class="w-full px-4 py-2 text-left hover:bg-gray-100 flex items-center gap-2" onclick="designer.browseTable('${table.name}')">
                <i class="fas fa-list text-green-600 w-4"></i>
                <span>Browse Data</span>
            </button>
            <button class="w-full px-4 py-2 text-left hover:bg-gray-100 flex items-center gap-2" onclick="designer.addColumn('${table.name}')">
                <i class="fas fa-plus text-purple-600 w-4"></i>
                <span>Add Column</span>
            </button>
            <button class="w-full px-4 py-2 text-left hover:bg-gray-100 flex items-center gap-2" onclick="designer.addRelationship('${table.name}')">
                <i class="fas fa-link text-indigo-600 w-4"></i>
                <span>Add Relationship</span>
            </button>
            <hr class="my-1">
            <button class="w-full px-4 py-2 text-left hover:bg-gray-100 flex items-center gap-2" onclick="designer.duplicateTable('${table.name}')">
                <i class="fas fa-copy text-gray-600 w-4"></i>
                <span>Duplicate Table</span>
            </button>
            <button class="w-full px-4 py-2 text-left hover:bg-gray-100 flex items-center gap-2" onclick="designer.exportTableSQL('${table.name}')">
                <i class="fas fa-file-code text-gray-600 w-4"></i>
                <span>Export SQL</span>
            </button>
            <hr class="my-1">
            <button class="w-full px-4 py-2 text-left hover:bg-red-50 text-red-600 flex items-center gap-2" onclick="designer.dropTable('${table.name}')">
                <i class="fas fa-trash w-4"></i>
                <span>Drop Table</span>
            </button>
        `;
        
        document.body.appendChild(menu);
        
        // Close menu on click outside
        setTimeout(() => {
            document.addEventListener('click', function closeMenu() {
                menu.remove();
                document.removeEventListener('click', closeMenu);
            });
        }, 10);
    }
    
    showCanvasContextMenu(event) {
        // Remove any existing context menu
        const existingMenu = document.getElementById('context-menu');
        if (existingMenu) existingMenu.remove();
        
        // Create context menu
        const menu = document.createElement('div');
        menu.id = 'context-menu';
        menu.className = 'fixed bg-white rounded-lg shadow-xl border border-gray-200 py-2 z-50';
        menu.style.left = event.clientX + 'px';
        menu.style.top = event.clientY + 'px';
        
        menu.innerHTML = `
            <button class="w-full px-4 py-2 text-left hover:bg-gray-100 flex items-center gap-2" onclick="designer.createNewTable()">
                <i class="fas fa-plus text-green-600 w-4"></i>
                <span>Create New Table</span>
            </button>
            <button class="w-full px-4 py-2 text-left hover:bg-gray-100 flex items-center gap-2" onclick="designer.autoLayout()">
                <i class="fas fa-magic text-blue-600 w-4"></i>
                <span>Auto Arrange</span>
            </button>
            <button class="w-full px-4 py-2 text-left hover:bg-gray-100 flex items-center gap-2" onclick="designer.resetZoom()">
                <i class="fas fa-compress text-gray-600 w-4"></i>
                <span>Reset View</span>
            </button>
        `;
        
        document.body.appendChild(menu);
        
        // Close menu on click outside
        setTimeout(() => {
            document.addEventListener('click', function closeMenu() {
                menu.remove();
                document.removeEventListener('click', closeMenu);
            });
        }, 10);
    }
    
    // Table operations
    editTable(tableName) {
        const table = this.tables.find(t => t.name === tableName);
        if (table) {
            this.openTableEditor(table);
        }
    }
    
    browseTable(tableName) {
        window.location.href = `index.php?database=${this.database}&table=${tableName}`;
    }
    
    addColumn(tableName) {
        openAddColumnModal(tableName);
    }
    
    addRelationship(tableName) {
        openAddRelationshipModal(tableName);
    }
    
    duplicateTable(tableName) {
        const newName = prompt(`Enter name for duplicated table:`, `${tableName}_copy`);
        if (newName) {
            showToast('Duplicating table...', 'info');
            // Implementation would go here
        }
    }
    
    exportTableSQL(tableName) {
        const table = this.tables.find(t => t.name === tableName);
        if (table) {
            // Generate CREATE TABLE statement
            let sql = `CREATE TABLE \`${tableName}\` (\n`;
            
            table.columns.forEach((col, index) => {
                sql += `  \`${col.name}\` ${col.type}`;
                if (!col.null) sql += ' NOT NULL';
                if (col.default) sql += ` DEFAULT ${col.default}`;
                if (col.extra) sql += ` ${col.extra}`;
                if (index < table.columns.length - 1) sql += ',';
                sql += '\n';
            });
            
            sql += ');';
            
            // Download as file
            const blob = new Blob([sql], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${tableName}.sql`;
            a.click();
            
            showToast('SQL exported successfully');
        }
    }
    
    async dropTable(tableName) {
        if (!confirm(`Are you sure you want to drop table "${tableName}"? This action cannot be undone!`)) {
            return;
        }
        
        try {
            const response = await fetch('handlers/designer_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'drop_table',
                    database: this.database,
                    table: tableName
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast('Table dropped successfully');
                this.loadSchema();
            } else {
                showToast(data.message, 'error');
            }
        } catch (error) {
            console.error('Error dropping table:', error);
            showToast('Error dropping table', 'error');
        }
    }
    
    createNewTable() {
        openCreateTableModal();
    }
}

// Initialize designer when page loads
let designer;
if (document.getElementById('designer-canvas')) {
    designer = new DatabaseDesignerApp();
}
