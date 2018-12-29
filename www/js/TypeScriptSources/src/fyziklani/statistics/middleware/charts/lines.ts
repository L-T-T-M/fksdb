import * as d3 from 'd3';
import { IExtendedSubmit } from '../../components/charts/team/line-chart/chart';

interface IScales {
    xScale: d3.ScaleTime<number, number>;
    yScale: d3.ScaleLinear<number, number>;
}

export interface IPointData {
    created: string;
    totalPoints: number;
}

export function getLinePath(scales: IScales, data: IPointData[]): string {
    const {xScale, yScale} = scales;
    return d3.line<IPointData>()
        .x((element: IExtendedSubmit) => {
            return xScale(new Date(element.created));
        })
        .y((element: IExtendedSubmit) => {
            return yScale(element.totalPoints);
        })(data);
}
