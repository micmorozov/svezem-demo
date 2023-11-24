#encoding "utf-8"
#GRAMMAR_ROOT S

CargoComplexName -> AnyWord<kwtype="груз">;

//Полное наименование может состоять из прилагательного и согласующего с ним существительного(ых)
//главным в цепочке является существительное (rt)
FullCargoName -> (Adj<gnc-agr[1]>*) CargoComplexName<gnc-agr[1], rt>;

S -> FullCargoName interp(Cargo.Real::not_norm; Cargo.Name; Cargo.NameRod::norm="gen"; Cargo.NameVin::norm="acc");
