import * as d3 from 'd3';

export function initializeNigeriaMap(elementId, statesData) {
    console.log('Initializing Nigeria Map with D3.js...');
    
    // Check element exists
    const element = document.getElementById(elementId);
    if (!element) {
        console.error('Map container not found:', elementId);
        return null;
    }
    
    // Convert object to array if needed
    let statesArray = statesData;
    if (!Array.isArray(statesData)) {
        console.log('Converting object to array...');
        statesArray = Object.values(statesData);
    }
    
    console.log(`Loading ${statesArray.length} states...`);
    
    // Get container dimensions
    const containerWidth = element.offsetWidth || 1100;
    const containerHeight = element.offsetHeight || 500;
    
    // Clear any existing content
    d3.select(`#${elementId}`).selectAll("*").remove();
    
    // Create SVG
    const svg = d3.select(`#${elementId}`)
        .append('svg')
        .attr('width', containerWidth)
        .attr('height', containerHeight)
        .attr('viewBox', `0 0 ${containerWidth} ${containerHeight}`)
        .attr('preserveAspectRatio', 'xMidYMid meet')
        .style('background-color', 'white'); // White background matching reference
    
    // Group for states
    const g = svg.append('g').attr('class', 'states-group');
    
    // Build GeoJSON from database data
    const features = statesArray
        .filter(state => state.map && state.map !== null)
        .map((state, index) => ({
            type: 'Feature',
            properties: {
                id: state.id,
                name: state.name,
                code: state.code,
                total_beneficiaries: parseInt(state.total_beneficiaries) || 0,
                active_beneficiaries: parseInt(state.active_beneficiaries) || 0,
                exited_beneficiaries: parseInt(state.exited_beneficiaries) || 0,
                phc_count: parseInt(state.phc_count) || 0,
                total_cash_disbursed: state.total_cash_disbursed || '0.00',
                index: index // Store index for color assignment
            },
            geometry: state.map // GeoJSON geometry from database
        }));
    
    const geojson = {
        type: 'FeatureCollection',
        features: features
    };
    
    console.log(`Rendering ${features.length} states on map`);
    
    if (features.length === 0) {
        console.warn('No features to render');
        return null;
    }
    
    // Create projection using fitSize (automatically fits to container)
    const projection = d3.geoMercator()
        .fitSize([containerWidth, containerHeight], geojson);
    
    const pathGen = d3.geoPath().projection(projection);
    
    // Create color scale matching the reference map
    // Use domain [0, 36] like the reference, but map states to skip very light colors
    const color = d3.scaleSequential()
        .domain([0, 36])
        .interpolator(d3.interpolateBlues);
    
    // Create deterministic color assignment based on state ID
    // This ensures the same state always gets the same color, even after page reload
    const minColorIndex = 5; // Skip first 5 lightest colors
    const maxColorIndex = 36;
    const colorRange = maxColorIndex - minColorIndex + 1;
    
    // Simple hash function for deterministic color assignment
    // This creates a mixed but consistent color pattern based on state ID
    const hashStateId = (stateId) => {
        // Use a simple hash to distribute colors evenly
        // Multiply by a prime number to create better distribution
        return ((stateId * 137) % colorRange) + minColorIndex;
    };
    
    // Use state ID to consistently assign colors (so same state always gets same color)
    // This creates a mixed but deterministic color pattern
    const getStateColor = (stateId, index) => {
        // Use deterministic hash function based on state ID
        const colorIndex = hashStateId(stateId);
        return color(colorIndex);
    };
    
    // Create tooltip
    const tooltip = d3.select('body')
        .append('div')
        .attr('class', 'map-tooltip')
        .style('position', 'absolute')
        .style('pointer-events', 'none')
        .style('padding', '8px 10px')
        .style('background', 'rgba(0,0,0,0.8)')
        .style('color', 'white')
        .style('border-radius', '6px')
        .style('font-size', '13px')
        .style('opacity', 0)
        .style('z-index', 1000);
    
    console.log(`Rendering ${features.length} states on map`);
    
    // Draw states
    g.selectAll('path.state')
        .data(features)
        .join('path')
        .attr('class', 'state')
        .attr('data-state-id', d => d.properties.id)
        .attr('data-state-name', d => d.properties.name)
        .attr('d', d => pathGen(d))
        .attr('fill', (d, i) => getStateColor(d.properties.id, i)) // Use mixed color assignment based on state ID
        .attr('stroke', '#ffffffcc') // White stroke with transparency (matching reference)
        .attr('stroke-width', 0.6) // Matching reference stroke width
        .style('cursor', 'pointer')
        .on('mouseover', function(event, d) {
            // Highlight on hover (matching reference style)
            d3.select(this)
                .attr('opacity', 0.9)
                .attr('stroke', '#00000055') // Black stroke with transparency on hover
                .attr('stroke-width', 1.2); // Thicker stroke on hover
            
            // Show tooltip
            const props = d.properties;
            const tooltipContent = `
                <div style="padding: 8px;">
                    <h3 style="font-weight: bold; font-size: 16px; margin-bottom: 8px;">${props.name}</h3>
                    <div style="font-size: 12px; line-height: 1.6;">
                        <div style="display: flex; justify-content: space-between; gap: 16px;">
                            <span>Beneficiaries:</span>
                            <strong style="color: #3b82f6;">${parseInt(props.total_beneficiaries).toLocaleString()}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; gap: 16px;">
                            <span>Active:</span>
                            <strong style="color: #10b981;">${parseInt(props.active_beneficiaries).toLocaleString()}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; gap: 16px;">
                            <span>PHCs:</span>
                            <strong style="color: #a855f7;">${props.phc_count}</strong>
                        </div>
                    </div>
                    <div style="margin-top: 8px; font-size: 11px; color: #d1d5db; font-style: italic; text-align: center;">
                        👆 Click for detailed information
                    </div>
                </div>
            `;
            
            tooltip
                .html(tooltipContent)
                .style('left', (event.pageX) + 'px')
                .style('top', (event.pageY - 10) + 'px')
                .style('opacity', 1);
        })
        .on('mousemove', function(event) {
            tooltip
                .style('left', (event.pageX) + 'px')
                .style('top', (event.pageY - 10) + 'px');
        })
        .on('mouseout', function() {
            // Reset style (matching reference)
            d3.select(this)
                .attr('opacity', 1)
                .attr('stroke', '#ffffffcc')
                .attr('stroke-width', 0.6);
            
            // Hide tooltip
            tooltip.style('opacity', 0);
        })
        .on('click', function(event, d) {
            // Dispatch custom event for immediate modal display
            window.dispatchEvent(new CustomEvent('state-selected', { detail: { stateId: d.properties.id } }));
            
            // Dispatch event to Livewire
            if (window.Livewire) {
                window.Livewire.dispatch('state-clicked', { stateId: d.properties.id });
            } else {
                console.log('State clicked:', d.properties.name, d.properties.id);
            }
        });
    
    console.log('Map rendered successfully');
    
    // Return the SVG element for potential future manipulation
    return {
        svg: svg.node(),
        projection: projection,
        pathGen: pathGen
    };
}
