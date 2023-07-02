import { Chord, chord, ChordGroup, Chords, ribbon } from 'd3-chord';
import { scaleOrdinal } from 'd3-scale';
import { schemeCategory10 } from 'd3-scale-chromatic';
import { arc } from 'd3-shape';
import * as React from 'react';
import './style.scss';
import { availableLanguage, Translator } from '@translator/translator';

export interface Data {
    person: {
        name: string;
        gender: 'M' | 'F';
    };
    participation: number[];
}

interface State {
    activeId: number;
}

export interface OwnProps {
    data: Data[];
    translator: Translator<availableLanguage>;
}

export default class ParticipantAcquaintanceChart extends React.Component<OwnProps, State> {
    private readonly innerRadius = 320;
    private readonly outerRadius = 340;
    private readonly textRadius = 360;

    constructor(props) {
        super(props);
        this.state = {activeId: null};
    }

    public render() {
        const matrix = this.calculateData();
        const layout = chord().padAngle(0.05)(matrix);
        return <div className="chart-participant-acquaintance">
            <svg viewBox="0 0 1200 1200" className="chart">
                <g transform="translate(600,600)">
                    {this.getChord(layout)}
                    {this.getLabels(matrix, layout.groups)}
                    {this.getArc(layout.groups)}
                </g>
            </svg>
        </div>;
    }

    private getArc(groups: ChordGroup[]): JSX.Element {
        const arcGenerator = arc<ChordGroup>()
            .innerRadius(this.innerRadius)
            .outerRadius(this.outerRadius);

        return <>{groups.map((datum, index) => {
            let className = '';
            if (this.state.activeId !== null && datum.index === this.state.activeId) {
                className = 'active';
            }

            return <path
                key={index}
                className={'arc ' + className}
                d={arcGenerator(datum)}
                style={{'--color': this.getPerson(index).person.gender === 'M' ? 'blue' : 'deeppink'} as React.CSSProperties}
                onClick={() => {
                    this.setState({activeId: this.state.activeId === index ? null : index});
                }}/>;
        })
        }</>;
    }

    private getLabels(matrix: number[][], groups: ChordGroup[]): JSX.Element {
        const textArc = arc<ChordGroup>().innerRadius(this.textRadius).outerRadius(this.textRadius);
        const {activeId} = this.state;
        return <>{groups.map((datum, index) => {
            const angle = ((datum.startAngle + datum.endAngle) / 2);
            const isOther = angle < Math.PI;
            let count = null;
            const isActive = activeId !== null && activeId === datum.index;
            if (activeId !== null) {
                if (activeId !== datum.index) {
                    count = matrix[datum.index][activeId];
                }
            } else {
                count = datum.value;
            }

            return <g
                key={index}
                transform={'translate(' + textArc.centroid(datum).join(',') + ')'}
            >
                <text
                    className={'label' + (isActive ? ' active' : '') + (isOther ? ' other' : '')}
                    transform={'rotate(' + ((isOther ? (angle - Math.PI / 2) : angle + Math.PI / 2) * 180 / Math.PI) + ')'}
                >{this.getPerson(index).person.name}
                    {count !== null ? (' (' + count + ')') : null}</text>
            </g>;
        })}</>;

    }

    private getPerson(index: number): Data {
        const {data} = this.props;
        return data[index];
    }

    private getChord(layout: Chords): JSX.Element {
        const colorScale = scaleOrdinal(schemeCategory10);
        const ribbonCreator = ribbon<Chord, string>().radius(this.innerRadius);
        return <>
            {layout.map((datum, index) => {
                let className = 'default';
                if (this.state.activeId !== null) {
                    className = 'inactive';
                }
                if (datum.source.index === this.state.activeId || datum.target.index === this.state.activeId) {
                    className = 'active';
                }

                // @ts-ignore
                const dAttr: string = ribbonCreator(datum);
                return <path
                    key={index}
                    className={'ribbon ' + className}
                    d={dAttr}
                    style={{'--color': colorScale(datum.source.index + '-' + datum.target.index)} as React.CSSProperties}
                />;
            })}
        </>;
    }

    private calculateData(): number[][] {
        const {data} = this.props;
        const {activeId} = this.state;
        const matrix = [];
        data.forEach((personA, indexA) => {
            matrix[indexA] = [];
            data.forEach((personB, indexB) => {
                if (personB.person === personA.person) {
                    matrix[indexA][indexB] = 0;
                    return;
                }
                if (activeId !== null && (indexA !== activeId && indexB !== activeId)) {
                    matrix[indexA][indexB] = 0;
                    return;
                }
                matrix[indexA][indexB] = personA.participation.reduce((count, eventId) => {
                    if (personB.participation.indexOf(eventId) !== -1) {
                        return count + 1;
                    }
                    return count;
                }, 0);
            });
        });
        return matrix;
    }
}
